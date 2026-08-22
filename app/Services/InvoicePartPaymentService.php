<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class InvoicePartPaymentService
{
    public function getRemainingBalance(int $parent_invoice_id): float
    {
        if (! Invoice::supportsPartPayments()) {
            return 0;
        }

        $parent = Invoice::query()->source()->findOrFail($parent_invoice_id);
        $allocated = (float) Invoice::query()
            ->where('parent_invoice_id', $parent->id)
            ->sum('part_payment_amount');

        return round(max((float) $parent->total - $allocated, 0), 2);
    }

    public function validatePartPaymentAmount(int $parent_invoice_id, float $amount): void
    {
        if (! Invoice::supportsPartPayments()) {
            throw ValidationException::withMessages([
                'amount' => 'Part payment invoices are not ready yet. Please run the latest database migrations.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'The part payment amount must be greater than zero.',
            ]);
        }

        $remaining = $this->getRemainingBalance($parent_invoice_id);

        if (round($amount, 2) > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'The part payment amount exceeds the remaining balance of '.number_format($remaining, 2).'.',
            ]);
        }
    }
}
