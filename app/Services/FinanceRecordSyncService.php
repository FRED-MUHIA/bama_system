<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinanceRecordSyncService
{
    public function invoices(): Collection
    {
        if (! Schema::hasTable('invoices')) {
            return collect();
        }

        $with = ['client'];
        if (Schema::hasTable('payments')) {
            $with[] = 'payments.paymentMethod';
        }

        return Invoice::source()
            ->with($with)
            ->get()
            ->map(fn (Invoice $invoice) => $this->applyInvoiceSnapshot($invoice, true))
            ->values();
    }

    public function receivables(): Collection
    {
        return $this->invoices()
            ->filter(fn (Invoice $invoice) => (float) $invoice->balance > 0)
            ->values();
    }

    public function supplierInvoices(): Collection
    {
        if (! Schema::hasTable('supplier_invoices')) {
            return collect();
        }

        $with = ['supplier'];
        if (Schema::hasTable('supplier_payments')) {
            $with[] = 'payments';
        }

        return SupplierInvoice::with($with)
            ->get()
            ->map(fn (SupplierInvoice $invoice) => $this->applySupplierSnapshot($invoice, true))
            ->values();
    }

    public function payables(): Collection
    {
        return $this->supplierInvoices()
            ->filter(fn (SupplierInvoice $invoice) => (float) $invoice->outstanding_balance > 0)
            ->values();
    }

    public function syncInvoiceId(?int $invoiceId, ?int $businessId = null, bool $preserveLegacyPaid = false): ?Invoice
    {
        if (! $invoiceId || ! Schema::hasTable('invoices')) {
            return null;
        }

        $query = Invoice::withoutGlobalScope('business')->whereKey($invoiceId);
        if ($businessId && Schema::hasColumn('invoices', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        $invoice = $query->first();

        return $invoice ? $this->syncInvoice($invoice, $preserveLegacyPaid) : null;
    }

    public function syncInvoice(Invoice $invoice, bool $preserveLegacyPaid = false): Invoice
    {
        $snapshot = $this->invoiceSnapshot($invoice, $preserveLegacyPaid);
        $changes = [
            'amount_paid' => $snapshot['amount_paid'],
            'balance' => $snapshot['balance'],
            'payment_status' => $snapshot['payment_status'],
        ];

        if ($this->needsNumericUpdate($invoice, $changes, ['amount_paid', 'balance'])
            || $this->normalizedStatus($invoice->payment_status) !== $changes['payment_status']) {
            $invoice->forceFill($changes)->save();
        }

        return $invoice->refresh();
    }

    public function syncSupplierInvoiceId(?int $invoiceId, ?int $businessId = null, bool $preserveLegacyPaid = false): ?SupplierInvoice
    {
        if (! $invoiceId || ! Schema::hasTable('supplier_invoices')) {
            return null;
        }

        $query = SupplierInvoice::withoutGlobalScope('business')->whereKey($invoiceId);
        if ($businessId && Schema::hasColumn('supplier_invoices', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        $invoice = $query->first();

        return $invoice ? $this->syncSupplierInvoice($invoice, $preserveLegacyPaid) : null;
    }

    public function syncSupplierInvoice(SupplierInvoice $invoice, bool $preserveLegacyPaid = false): SupplierInvoice
    {
        $snapshot = $this->supplierSnapshot($invoice, $preserveLegacyPaid);
        $changes = [
            'amount_paid' => $snapshot['amount_paid'],
            'status' => $snapshot['status'],
        ];

        if ($this->needsNumericUpdate($invoice, $changes, ['amount_paid'])
            || Str::lower((string) $invoice->status) !== Str::lower($changes['status'])) {
            $invoice->forceFill($changes)->save();
        }

        return $invoice->refresh();
    }

    public function applyInvoiceSnapshot(Invoice $invoice, bool $preserveLegacyPaid = true): Invoice
    {
        $snapshot = $this->invoiceSnapshot($invoice, $preserveLegacyPaid);
        foreach ($snapshot as $attribute => $value) {
            $invoice->setAttribute($attribute, $value);
        }

        return $invoice;
    }

    public function applySupplierSnapshot(SupplierInvoice $invoice, bool $preserveLegacyPaid = true): SupplierInvoice
    {
        $snapshot = $this->supplierSnapshot($invoice, $preserveLegacyPaid);
        $invoice->setAttribute('amount_paid', $snapshot['amount_paid']);
        $invoice->setAttribute('status', $snapshot['status']);
        $invoice->setAttribute('outstanding_balance', $snapshot['outstanding_balance']);

        return $invoice;
    }

    public function invoiceSnapshot(Invoice $invoice, bool $preserveLegacyPaid = true): array
    {
        [$paymentCount, $paymentTotal] = $this->customerPaymentTotals($invoice);
        $total = round((float) $invoice->total, 2);
        $paid = $paymentTotal;

        if ($preserveLegacyPaid && $paymentCount === 0) {
            $paid = max($paid, $this->legacyPaidAmount($invoice));
        }

        $paid = round(max($paid, 0), 2);
        $balance = round(max($total - $paid, 0), 2);

        return [
            'amount_paid' => $paid,
            'balance' => $balance,
            'payment_status' => $this->invoicePaymentStatus($total, $paid, $balance),
        ];
    }

    public function supplierSnapshot(SupplierInvoice $invoice, bool $preserveLegacyPaid = true): array
    {
        [$paymentCount, $paymentTotal] = $this->supplierPaymentTotals($invoice);
        $total = round((float) $invoice->total, 2);
        $paid = $paymentTotal;

        if ($preserveLegacyPaid && $paymentCount === 0) {
            $paid = max($paid, $this->legacyPaidAmount($invoice));
        }

        $paid = round(max($paid, 0), 2);
        $balance = round(max($total - $paid, 0), 2);

        return [
            'amount_paid' => $paid,
            'outstanding_balance' => $balance,
            'status' => $this->supplierPaymentStatus($total, $paid, $balance),
        ];
    }

    private function customerPaymentTotals(Invoice $invoice): array
    {
        if (! Schema::hasTable('payments')) {
            return [0, 0.0];
        }

        if ($invoice->relationLoaded('payments')) {
            return [$invoice->payments->count(), round((float) $invoice->payments->sum('amount'), 2)];
        }

        $query = Payment::withoutGlobalScope('business')->where('invoice_id', $invoice->id);
        $this->scopeBusiness($query, 'payments', $invoice->business_id);

        return [(clone $query)->count(), round((float) $query->sum('amount'), 2)];
    }

    private function supplierPaymentTotals(SupplierInvoice $invoice): array
    {
        if (! Schema::hasTable('supplier_payments')) {
            return [0, 0.0];
        }

        if ($invoice->relationLoaded('payments')) {
            return [$invoice->payments->count(), round((float) $invoice->payments->sum('amount'), 2)];
        }

        $query = SupplierPayment::withoutGlobalScope('business')->where('supplier_invoice_id', $invoice->id);
        $this->scopeBusiness($query, 'supplier_payments', $invoice->business_id);

        return [(clone $query)->count(), round((float) $query->sum('amount'), 2)];
    }

    private function scopeBusiness($query, string $table, ?int $businessId): void
    {
        if ($businessId && Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }
    }

    private function legacyPaidAmount(Model $record): float
    {
        $total = round((float) $record->total, 2);
        $storedPaid = round(max((float) ($record->amount_paid ?? 0), 0), 2);
        $storedBalance = round((float) ($record->balance ?? $record->outstanding_balance ?? $total), 2);
        $status = $this->normalizedStatus($record instanceof SupplierInvoice ? $record->status : $record->payment_status);

        if ($storedPaid > 0) {
            return $storedPaid;
        }

        if ($storedBalance > 0 && $storedBalance < $total) {
            return round($total - $storedBalance, 2);
        }

        return in_array($status, ['paid', 'settled', 'complete', 'completed'], true) && $total > 0 ? $total : 0.0;
    }

    private function invoicePaymentStatus(float $total, float $paid, float $balance): string
    {
        if ($total <= 0 || $balance <= 0) {
            return 'paid';
        }

        return $paid > 0 ? 'partial' : 'unpaid';
    }

    private function supplierPaymentStatus(float $total, float $paid, float $balance): string
    {
        if ($total <= 0 || $balance <= 0) {
            return 'Paid';
        }

        return $paid > 0 ? 'Partial' : 'Unpaid';
    }

    private function normalizedStatus(?string $status): string
    {
        return Str::of((string) $status)->lower()->replace([' ', '-'], '_')->toString();
    }

    private function needsNumericUpdate(Model $model, array $changes, array $numericFields): bool
    {
        foreach ($numericFields as $field) {
            if (array_key_exists($field, $changes)
                && abs(round((float) $model->{$field}, 2) - round((float) $changes[$field], 2)) > 0.005) {
                return true;
            }
        }

        return false;
    }
}
