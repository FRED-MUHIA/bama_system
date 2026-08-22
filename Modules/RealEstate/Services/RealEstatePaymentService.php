<?php

namespace Modules\RealEstate\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Services\DocumentService;
use App\Services\InvoicePosOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\RealEstate\Models\AmenityBooking;
use Modules\RealEstate\Models\RentalCharge;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\UtilityBill;

class RealEstatePaymentService
{
    public function __construct(
        private DocumentService $documents,
        private InvoicePosOrderService $invoiceOrders,
        private TenantLedgerService $ledger,
    ) {}

    public function recordClientPayment(Invoice $invoice, array $data): Receipt
    {
        if ($invoice->isPartPayment()) {
            throw ValidationException::withMessages(['amount' => 'Payments must be recorded against the parent invoice.']);
        }

        if ((float) $invoice->balance <= 0) {
            throw ValidationException::withMessages(['invoice_id' => 'This invoice is already fully paid.']);
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount > (float) $invoice->balance) {
            throw ValidationException::withMessages(['amount' => 'The payment amount cannot exceed the invoice balance.']);
        }

        return DB::transaction(function () use ($invoice, $data, $amount) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $payment = $invoice->payments()->create([
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'department_id' => $invoice->department_id,
                'cost_center_id' => $invoice->cost_center_id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $paid = (float) $invoice->payments()->sum('amount');
            $balance = max((float) $invoice->total - $paid, 0);
            $invoice->update([
                'amount_paid' => $paid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : 'partial',
            ]);
            $this->invoiceOrders->sync($invoice);

            $receiptData = [
                'payment_id' => $payment->id,
                'receipt_number' => $this->documents->number('receipt'),
                'amount_paid' => $payment->amount,
                'balance_remaining' => $balance,
                'status' => $balance <= 0 ? 'Paid' : 'Partial',
                'payment_method' => $payment->paymentMethod?->name,
                'payment_date' => $payment->payment_date,
            ];

            if (Schema::hasColumn('receipts', 'project_id') && $invoice->project_id) {
                $receiptData['project_id'] = $invoice->project_id;
            }

            $receipt = $invoice->receipts()->create($receiptData);

            if (Schema::hasTable('receipt_allocations')) {
                ReceiptAllocation::create([
                    'business_id' => $invoice->business_id,
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $invoice->id,
                    'project_id' => $invoice->project_id,
                    'amount' => $payment->amount,
                ]);
            }

            $this->syncRealEstateRecords($invoice, $balance);

            if ($tenant = $this->tenantForInvoice($invoice)) {
                $this->ledger->syncPayments($tenant);
            }

            return $receipt;
        });
    }

    public function oldestOutstandingInvoiceForClient(int $clientId): ?Invoice
    {
        return $this->oldestOutstandingInvoice(['client_id' => $clientId]);
    }

    public function oldestOutstandingInvoiceForTenant(int $tenantId): ?Invoice
    {
        return $this->oldestOutstandingInvoice(['tenant_id' => $tenantId]);
    }

    public function oldestOutstandingInvoiceForUnit(int $unitId): ?Invoice
    {
        return $this->oldestOutstandingInvoice(['unit_id' => $unitId]);
    }

    public function isRealEstateInvoice(Invoice $invoice): bool
    {
        return $this->realEstateInvoiceIds()->contains((int) $invoice->id);
    }

    public function invoiceContext(int $invoiceId): array
    {
        $tenant = $this->tenantForInvoiceId($invoiceId);

        return [
            'client_id' => $tenant?->client_id,
            'tenant_id' => $tenant?->id,
            'unit_id' => $this->unitIdForInvoiceId($invoiceId),
        ];
    }

    private function syncRealEstateRecords(Invoice $invoice, float $balance): void
    {
        $status = $balance <= 0 ? 'Paid' : 'Partial';

        RentalCharge::where('invoice_id', $invoice->id)->update(['status' => $status]);
        UtilityBill::where('invoice_id', $invoice->id)->update(['status' => $status]);
    }

    private function tenantForInvoice(Invoice $invoice): ?Tenant
    {
        return $this->tenantForInvoiceId((int) $invoice->id);
    }

    private function tenantForInvoiceId(int $invoiceId): ?Tenant
    {
        $rentalCharge = RentalCharge::with('lease.tenant')->where('invoice_id', $invoiceId)->first();
        if ($rentalCharge?->lease?->tenant) {
            return $rentalCharge->lease->tenant;
        }

        $utilityBill = UtilityBill::with('tenant')->where('invoice_id', $invoiceId)->first();
        if ($utilityBill?->tenant) {
            return $utilityBill->tenant;
        }

        return AmenityBooking::with('tenant')->where('invoice_id', $invoiceId)->first()?->tenant;
    }

    private function unitIdForInvoiceId(int $invoiceId): ?int
    {
        $rentalCharge = RentalCharge::with('lease')->where('invoice_id', $invoiceId)->first();
        if ($rentalCharge?->lease?->real_estate_unit_id) {
            return (int) $rentalCharge->lease->real_estate_unit_id;
        }

        $utilityBill = UtilityBill::where('invoice_id', $invoiceId)->first();
        if ($utilityBill?->real_estate_unit_id) {
            return (int) $utilityBill->real_estate_unit_id;
        }

        return AmenityBooking::where('invoice_id', $invoiceId)->value('real_estate_unit_id');
    }

    private function oldestOutstandingInvoice(array $filters): ?Invoice
    {
        $invoiceIds = $this->realEstateInvoiceIds();

        if ($invoiceIds->isEmpty()) {
            return null;
        }

        return Invoice::whereIn('id', $invoiceIds)
            ->where('balance', '>', 0)
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['tenant_id'] ?? null, function ($query, $tenantId) {
                $query->whereIn('id', $this->invoiceIdsForTenant((int) $tenantId));
            })
            ->when($filters['unit_id'] ?? null, function ($query, $unitId) {
                $query->whereIn('id', $this->invoiceIdsForUnit((int) $unitId));
            })
            ->orderBy('due_date')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->first();
    }

    private function invoiceIdsForTenant(int $tenantId)
    {
        return collect()
            ->merge(RentalCharge::whereHas('lease', fn ($query) => $query->where('real_estate_tenant_id', $tenantId))->pluck('invoice_id'))
            ->merge(UtilityBill::where('real_estate_tenant_id', $tenantId)->pluck('invoice_id'))
            ->merge(AmenityBooking::where('real_estate_tenant_id', $tenantId)->pluck('invoice_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function invoiceIdsForUnit(int $unitId)
    {
        return collect()
            ->merge(RentalCharge::whereHas('lease', fn ($query) => $query->where('real_estate_unit_id', $unitId))->pluck('invoice_id'))
            ->merge(UtilityBill::where('real_estate_unit_id', $unitId)->pluck('invoice_id'))
            ->merge(AmenityBooking::where('real_estate_unit_id', $unitId)->pluck('invoice_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function realEstateInvoiceIds()
    {
        return collect()
            ->merge(RentalCharge::whereNotNull('invoice_id')->pluck('invoice_id'))
            ->merge(UtilityBill::whereNotNull('invoice_id')->pluck('invoice_id'))
            ->merge(AmenityBooking::whereNotNull('invoice_id')->pluck('invoice_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
