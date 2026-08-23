<?php

namespace Modules\PrintingBranding\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PrintingBranding\Models\Estimate;
use Modules\PrintingBranding\Models\Material;
use Modules\PrintingBranding\Models\MaterialReservation;
use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Models\ProductionOperation;

class ProductionJobService
{
    public function __construct(
        private PrintingNumberService $numbers,
        private JobTicketService $tickets,
        private DocumentService $documents,
    ) {}

    public function create(array $data): ProductionJob
    {
        return DB::transaction(function () use ($data) {
            $job = ProductionJob::create($data + [
                'job_number' => $data['job_number'] ?? $this->numbers->next('JOB', ProductionJob::class, 'job_number'),
                'status' => $data['status'] ?? 'Draft',
            ]);

            $this->tickets->createForJob($job);

            if (in_array($job->status, ['Approved', 'Queued', 'In Production'], true)) {
                $this->reserveRequiredMaterials($job);
            }

            return $job;
        });
    }

    public function fromQuotation(Quotation $quotation, array $overrides = []): ProductionJob
    {
        $quotation->loadMissing('items');
        $item = $quotation->items->first();

        return $this->create($overrides + [
            'client_id' => $quotation->client_id,
            'quotation_id' => $quotation->id,
            'product_name' => $item?->title ?: Str::limit($item?->description ?: 'Printing Job', 120, ''),
            'quantity' => $item?->quantity ?: 1,
            'specifications' => ['source' => 'Quotation', 'quotation_number' => $quotation->quotation_number],
            'delivery_date' => now()->addDays(7)->toDateString(),
            'priority' => 'Normal',
            'status' => 'Approved',
        ]);
    }

    public function fromEstimate(Estimate $estimate, array $overrides = []): ProductionJob
    {
        return $this->create($overrides + [
            'client_id' => $estimate->client_id,
            'quotation_id' => $estimate->quotation_id,
            'estimate_id' => $estimate->id,
            'product_id' => $estimate->product_id,
            'product_name' => $estimate->product_name,
            'quantity' => $estimate->quantity,
            'specifications' => $estimate->specifications,
            'delivery_date' => now()->addDays(7)->toDateString(),
            'priority' => 'Normal',
            'status' => 'Approved',
        ]);
    }

    public function updateStatus(ProductionJob $job, string $status): ProductionJob
    {
        return DB::transaction(function () use ($job, $status) {
            $job->update([
                'status' => $status,
                'approved_at' => $status === 'Approved' ? now() : $job->approved_at,
                'completed_at' => $status === 'Completed' ? now() : $job->completed_at,
            ]);

            if ($status === 'Approved') {
                $this->reserveRequiredMaterials($job);
            }

            if ($status === 'Completed') {
                $this->releaseUnusedReservations($job);
            }

            return $job->refresh();
        });
    }

    public function syncStatusFromOperation(ProductionOperation $operation, ?string $action = null): ProductionJob
    {
        $operation->loadMissing('job');

        return $this->updateStatus($operation->job, $this->statusForOperation($operation, $action));
    }

    private function statusForOperation(ProductionOperation $operation, ?string $action = null): string
    {
        if ($action === 'pause' || $operation->status === 'Paused') {
            return 'On Hold';
        }

        if ($action === 'complete' || $operation->status === 'Completed') {
            return match ($operation->stage) {
                'Prepress', 'Printing' => 'Finishing',
                'Cutting', 'Lamination', 'Binding', 'Folding', 'Creasing', 'Embroidery', 'Heat Press', 'Mounting', 'Signage Fabrication', 'Packaging' => 'Quality Control',
                'Quality Check' => 'Ready for Dispatch',
                'Dispatch' => 'Dispatched',
                default => 'In Production',
            };
        }

        return match ($operation->stage) {
            'Printing' => 'Printing',
            'Cutting', 'Lamination', 'Binding', 'Folding', 'Creasing', 'Embroidery', 'Heat Press', 'Mounting', 'Signage Fabrication', 'Packaging' => 'Finishing',
            'Quality Check' => 'Quality Control',
            'Dispatch' => 'Ready for Dispatch',
            default => 'In Production',
        };
    }

    public function reserveRequiredMaterials(ProductionJob $job): void
    {
        foreach ($job->materials_required ?? [] as $line) {
            $material = Material::find($line['material_id'] ?? null);
            $quantity = (float) ($line['quantity'] ?? 0);
            if (! $material || $quantity <= 0) {
                continue;
            }

            if (((float) $material->stock_quantity - (float) $material->reserved_quantity) < $quantity) {
                abort(422, 'Material shortage for '.$material->name);
            }

            $reservation = MaterialReservation::firstOrCreate(
                ['job_id' => $job->id, 'material_id' => $material->id],
                ['required_quantity' => $quantity, 'reserved_quantity' => 0, 'status' => 'Reserved']
            );

            $delta = max($quantity - (float) $reservation->reserved_quantity, 0);
            if ($delta > 0) {
                $material->increment('reserved_quantity', $delta);
                $reservation->update(['required_quantity' => $quantity, 'reserved_quantity' => $quantity]);
            }
        }
    }

    public function consumeMaterial(MaterialReservation $reservation, float $quantity): MaterialReservation
    {
        return DB::transaction(function () use ($reservation, $quantity) {
            $material = Material::whereKey($reservation->material_id)->lockForUpdate()->firstOrFail();
            abort_if($quantity > (float) $material->stock_quantity, 422, 'Insufficient material stock.');

            $material->decrement('stock_quantity', $quantity);
            $material->decrement('reserved_quantity', min($quantity, (float) $material->reserved_quantity));
            $reservation->increment('consumed_quantity', $quantity);
            $reservation->update(['status' => (float) $reservation->fresh()->consumed_quantity >= (float) $reservation->reserved_quantity ? 'Consumed' : 'Partially Consumed']);

            if ($material->product_id) {
                app(\App\Services\StockService::class)->consume($material->product, $quantity, $reservation, $reservation->job?->job_number, 'Printing material consumed by production.');
            }

            return $reservation->refresh();
        });
    }

    public function releaseUnusedReservations(ProductionJob $job): void
    {
        foreach ($job->reservations()->with('material')->get() as $reservation) {
            $unused = max((float) $reservation->reserved_quantity - (float) $reservation->consumed_quantity, 0);
            if ($unused > 0 && $reservation->material) {
                $reservation->material->decrement('reserved_quantity', $unused);
            }
            $reservation->update(['reserved_quantity' => $reservation->consumed_quantity, 'status' => 'Closed']);
        }
    }

    public function invoice(ProductionJob $job, string $type = 'Final Invoice'): Invoice
    {
        return DB::transaction(function () use ($job, $type) {
            $amount = $job->cost?->selling_price ?: $job->quotation?->total ?: 0;
            $invoice = Invoice::create([
                'client_id' => $job->client_id,
                'quotation_id' => $job->quotation_id,
                'invoice_number' => $this->documents->number('invoice'),
                'public_token' => Str::random(48),
                'industry_module' => 'printing_branding',
                'industry_reference' => $job->job_number,
                'industry_context' => [
                    'invoice_type' => $type,
                    'production_job_id' => $job->id,
                    'job_number' => $job->job_number,
                    'product_name' => $job->product_name,
                    'quantity' => (float) $job->quantity,
                    'specifications' => $job->specifications ?? [],
                    'delivery_date' => $job->delivery_date?->toDateString(),
                    'priority' => $job->priority,
                    'job_status' => $job->status,
                    'machine' => $job->machine?->name,
                    'ticket_number' => $job->ticket?->ticket_number,
                ],
                'invoice_date' => now(),
                'due_date' => now()->addDays(14),
                'payment_status' => 'unpaid',
                'subtotal' => $amount,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => $amount,
                'amount_paid' => 0,
                'balance' => $amount,
                'notes' => $type.' for '.$job->job_number,
            ]);

            $invoice->items()->create([
                'title' => $job->product_name,
                'description' => $this->invoiceDescription($job),
                'quantity' => $job->quantity,
                'unit_price' => $job->quantity > 0 ? round($amount / $job->quantity, 2) : $amount,
                'discount' => 0,
                'tax_rate' => 0,
                'line_total' => $amount,
            ]);

            return $invoice;
        });
    }

    private function invoiceDescription(ProductionJob $job): string
    {
        $specs = collect($job->specifications ?? [])
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode(', ');

        return trim($job->job_number.' - '.$job->product_name.($specs ? ' | '.$specs : ''));
    }
}
