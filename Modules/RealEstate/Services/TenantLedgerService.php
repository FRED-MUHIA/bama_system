<?php

namespace Modules\RealEstate\Services;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Models\AmenityBooking;
use Modules\RealEstate\Models\RentalCharge;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\TenantLedger;
use Modules\RealEstate\Models\TenantStatement;
use Modules\RealEstate\Models\UtilityBill;

class TenantLedgerService
{
    public function __construct(private RealEstateNumberService $numbers) {}

    public function recordCharge(Tenant $tenant, Model $source, string $entryType, string $description, float $amount, array $context = []): TenantLedger
    {
        return $this->record($tenant, $source, $entryType, $description, $amount, 0, $context);
    }

    public function recordCredit(Tenant $tenant, Model $source, string $entryType, string $description, float $amount, array $context = []): TenantLedger
    {
        return $this->record($tenant, $source, $entryType, $description, 0, $amount, $context);
    }

    public function ledger(Tenant $tenant, ?string $start = null, ?string $end = null)
    {
        $this->syncPayments($tenant);

        return TenantLedger::with('invoice', 'payment', 'property', 'unit')
            ->where('real_estate_tenant_id', $tenant->id)
            ->when($start, fn ($query) => $query->whereDate('entry_date', '>=', $start))
            ->when($end, fn ($query) => $query->whereDate('entry_date', '<=', $end))
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();
    }

    public function statement(Tenant $tenant, string $start, string $end): TenantStatement
    {
        $this->syncPayments($tenant);

        $previousBalance = (float) TenantLedger::where('real_estate_tenant_id', $tenant->id)
            ->whereDate('entry_date', '<', $start)
            ->sum(DB::raw('debit - credit'));

        $entries = TenantLedger::where('real_estate_tenant_id', $tenant->id)
            ->whereDate('entry_date', '>=', $start)
            ->whereDate('entry_date', '<=', $end)
            ->get();

        $charges = (float) $entries->sum('debit');
        $payments = (float) $entries->sum('credit');

        return TenantStatement::create([
            'real_estate_tenant_id' => $tenant->id,
            'real_estate_lease_id' => $tenant->leases()->where('status', 'Active')->latest('id')->value('id'),
            'statement_number' => $this->numbers->next('real_estate_tenant_statements', 'statement_number', 'STM'),
            'period_start' => $start,
            'period_end' => $end,
            'previous_balance' => $previousBalance,
            'current_charges' => $charges,
            'payments_made' => $payments,
            'outstanding_balance' => $previousBalance + $charges - $payments,
            'summary' => [
                'rent' => (float) $entries->where('entry_type', 'Rent Charge')->sum('debit'),
                'utilities' => (float) $entries->where('entry_type', 'Utility Charge')->sum('debit'),
                'amenities' => (float) $entries->where('entry_type', 'Amenity Charge')->sum('debit'),
                'payments' => $payments,
            ],
        ]);
    }

    public function financialSummary(Tenant $tenant): array
    {
        $this->syncPayments($tenant);

        $entries = TenantLedger::where('real_estate_tenant_id', $tenant->id)->get();

        return [
            'outstanding_balance' => (float) ($entries->sum('debit') - $entries->sum('credit')),
            'total_paid' => (float) $entries->sum('credit'),
            'total_charges' => (float) $entries->sum('debit'),
            'utility_charges' => (float) $entries->where('entry_type', 'Utility Charge')->sum('debit'),
            'service_charges' => (float) $entries->whereIn('entry_type', ['Service Charge', 'Amenity Charge'])->sum('debit'),
        ];
    }

    public function syncPayments(Tenant $tenant): void
    {
        $invoiceIds = collect()
            ->merge(RentalCharge::whereHas('lease', fn ($query) => $query->where('real_estate_tenant_id', $tenant->id))->pluck('invoice_id'))
            ->merge(UtilityBill::where('real_estate_tenant_id', $tenant->id)->pluck('invoice_id'))
            ->merge(AmenityBooking::where('real_estate_tenant_id', $tenant->id)->pluck('invoice_id'))
            ->filter()
            ->unique()
            ->values();

        if ($invoiceIds->isEmpty()) {
            return;
        }

        Payment::with('invoice')
            ->whereIn('invoice_id', $invoiceIds)
            ->get()
            ->each(function (Payment $payment) use ($tenant) {
                if (TenantLedger::where('payment_id', $payment->id)->exists()) {
                    return;
                }

                $this->recordCredit($tenant, $payment, 'Payment', 'Payment received for '.$payment->invoice?->invoice_number, (float) $payment->amount, [
                    'invoice_id' => $payment->invoice_id,
                    'payment_id' => $payment->id,
                    'entry_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
                ]);
            });
    }

    private function record(Tenant $tenant, Model $source, string $entryType, string $description, float $debit, float $credit, array $context): TenantLedger
    {
        $existing = TenantLedger::where('ledgerable_type', $source->getMorphClass())
            ->where('ledgerable_id', $source->getKey())
            ->where('entry_type', $entryType)
            ->first();

        if ($existing) {
            return $existing;
        }

        $balance = (float) TenantLedger::where('real_estate_tenant_id', $tenant->id)->sum(DB::raw('debit - credit'));

        return TenantLedger::create([
            'real_estate_tenant_id' => $tenant->id,
            'real_estate_lease_id' => $context['real_estate_lease_id'] ?? null,
            'real_estate_property_id' => $context['real_estate_property_id'] ?? null,
            'real_estate_unit_id' => $context['real_estate_unit_id'] ?? null,
            'invoice_id' => $context['invoice_id'] ?? null,
            'payment_id' => $context['payment_id'] ?? null,
            'ledgerable_type' => $source->getMorphClass(),
            'ledgerable_id' => $source->getKey(),
            'entry_date' => $context['entry_date'] ?? Carbon::today()->toDateString(),
            'entry_type' => $entryType,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'running_balance' => round($balance + $debit - $credit, 2),
            'status' => $context['status'] ?? 'Posted',
        ]);
    }
}
