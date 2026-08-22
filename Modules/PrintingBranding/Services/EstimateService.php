<?php

namespace Modules\PrintingBranding\Services;

use App\Models\Quotation;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Modules\PrintingBranding\Models\Estimate;

class EstimateService
{
    public function __construct(private PrintingNumberService $numbers, private PrintPricingService $pricing, private DocumentService $documents) {}

    public function create(array $data): Estimate
    {
        return $this->numbers->transaction(function () use ($data) {
            $totals = $this->pricing->calculate($data);

            return Estimate::create($data + $totals + [
                'estimate_number' => $data['estimate_number'] ?? $this->numbers->next('EST', Estimate::class, 'estimate_number'),
                'status' => $data['status'] ?? 'Draft',
            ]);
        });
    }

    public function convertToQuotation(Estimate $estimate): Quotation
    {
        return DB::transaction(function () use ($estimate) {
            $quotation = Quotation::create([
                'client_id' => $estimate->client_id,
                'quotation_number' => $this->documents->number('quotation'),
                'quotation_date' => now(),
                'valid_until' => now()->addDays(14),
                'status' => 'draft',
                'subtotal' => $estimate->selling_price,
                'discount_total' => (float) ($estimate->discount ?? 0),
                'tax_total' => (float) ($estimate->tax ?? 0),
                'total' => $estimate->selling_price,
                'terms' => 'Printing estimate '.$estimate->estimate_number,
                'notes' => 'Converted from print estimate '.$estimate->estimate_number,
            ]);

            $quotation->items()->create([
                'title' => $estimate->product_name,
                'description' => $this->description($estimate),
                'quantity' => $estimate->quantity,
                'unit_price' => $estimate->quantity > 0 ? round($estimate->selling_price / $estimate->quantity, 2) : $estimate->selling_price,
                'discount' => 0,
                'tax_rate' => 0,
                'line_total' => $estimate->selling_price,
            ]);

            $estimate->update(['quotation_id' => $quotation->id, 'status' => 'Converted']);

            return $quotation;
        });
    }

    private function description(Estimate $estimate): string
    {
        $specs = collect($estimate->specifications ?? [])
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode(', ');

        return trim($estimate->product_name.($specs ? ' - '.$specs : ''));
    }
}
