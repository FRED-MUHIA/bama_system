<?php

namespace Modules\RealEstate\Services;

use App\Models\Invoice;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RealEstate\Models\Amenity;
use Modules\RealEstate\Models\AmenityBooking;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\MeterReading;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\UtilityBill;
use Modules\RealEstate\Models\UtilityConsumption;
use Modules\RealEstate\Models\UtilityInvoice;
use Modules\RealEstate\Models\UtilityMeter;
use Modules\RealEstate\Models\UtilityType;

class RealEstateUtilityBillingService
{
    public function __construct(
        private RealEstateNumberService $numbers,
        private DocumentService $documents,
        private TenantLedgerService $ledger,
        private RealEstateInvoiceProfileService $invoiceProfiles,
    ) {}

    public function createUtilityType(array $data): UtilityType
    {
        $data['code'] = $data['code'] ?? Str::upper(Str::slug($data['name'], '_'));
        return UtilityType::create($data);
    }

    public function createMeter(array $data): UtilityMeter
    {
        $utilityType = UtilityType::findOrFail($data['real_estate_utility_type_id']);
        $data['rate_per_unit'] = $data['rate_per_unit'] ?? $utilityType->default_rate;

        return UtilityMeter::create($data);
    }

    public function recordReading(array $data, bool $generateBill = true): MeterReading
    {
        return DB::transaction(function () use ($data, $generateBill) {
            $meter = UtilityMeter::with('utilityType')->findOrFail($data['real_estate_utility_meter_id']);
            $previous = (float) ($data['previous_reading'] ?? $meter->current_reading ?? 0);
            $current = (float) $data['current_reading'];
            $consumption = max($current - $previous, 0);
            $rate = (float) ($data['rate_per_unit'] ?? $meter->rate_per_unit ?: $meter->utilityType->default_rate);
            $amount = round($consumption * $rate, 2);

            $reading = MeterReading::create([
                'real_estate_utility_meter_id' => $meter->id,
                'real_estate_property_id' => $meter->real_estate_property_id,
                'real_estate_unit_id' => $meter->real_estate_unit_id,
                'real_estate_tenant_id' => $data['real_estate_tenant_id'] ?? $meter->real_estate_tenant_id,
                'real_estate_utility_type_id' => $meter->real_estate_utility_type_id,
                'previous_reading' => $previous,
                'current_reading' => $current,
                'consumption' => $consumption,
                'reading_date' => $data['reading_date'] ?? now()->toDateString(),
                'rate_per_unit' => $rate,
                'charge_amount' => $amount,
                'source' => $data['source'] ?? 'Manual Entry',
                'metadata' => $data['metadata'] ?? null,
            ]);

            $meter->update([
                'previous_reading' => $previous,
                'current_reading' => $current,
                'reading_date' => $reading->reading_date,
                'rate_per_unit' => $rate,
            ]);

            UtilityConsumption::create([
                'real_estate_tenant_id' => $reading->real_estate_tenant_id,
                'real_estate_unit_id' => $reading->real_estate_unit_id,
                'real_estate_utility_type_id' => $reading->real_estate_utility_type_id,
                'real_estate_meter_reading_id' => $reading->id,
                'consumption_date' => $reading->reading_date,
                'quantity' => $consumption,
                'amount' => $amount,
                'metadata' => ['source' => $reading->source],
            ]);

            if ($generateBill && $reading->real_estate_tenant_id) {
                $this->createUtilityBill([
                    'real_estate_tenant_id' => $reading->real_estate_tenant_id,
                    'real_estate_property_id' => $reading->real_estate_property_id,
                    'real_estate_unit_id' => $reading->real_estate_unit_id,
                    'real_estate_utility_type_id' => $reading->real_estate_utility_type_id,
                    'real_estate_meter_reading_id' => $reading->id,
                    'period_start' => $data['period_start'] ?? now()->startOfMonth()->toDateString(),
                    'period_end' => $data['period_end'] ?? now()->endOfMonth()->toDateString(),
                    'quantity' => $consumption,
                    'rate_per_unit' => $rate,
                    'amount' => $amount,
                    'due_date' => $data['due_date'] ?? now()->addDays(7)->toDateString(),
                ]);

                $reading->update(['status' => 'Billed']);
            }

            return $reading;
        });
    }

    public function createUtilityBill(array $data): UtilityBill
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::with('client')->findOrFail($data['real_estate_tenant_id']);
            $lease = Lease::where('real_estate_tenant_id', $tenant->id)
                ->where('status', 'Active')
                ->latest('id')
                ->first();
            $utilityType = UtilityType::findOrFail($data['real_estate_utility_type_id']);
            $amount = round((float) ($data['amount'] ?? (((float) ($data['quantity'] ?? 0) * (float) ($data['rate_per_unit'] ?? $utilityType->default_rate)) + (float) ($data['fixed_charge'] ?? 0))), 2);

            $invoice = $this->invoice($tenant, 'Utility - '.$utilityType->name, $amount, $data['due_date'] ?? now()->addDays(7)->toDateString());

            $bill = UtilityBill::create([
                'real_estate_tenant_id' => $tenant->id,
                'real_estate_lease_id' => $lease?->id,
                'real_estate_property_id' => $data['real_estate_property_id'] ?? $lease?->real_estate_property_id,
                'real_estate_unit_id' => $data['real_estate_unit_id'] ?? $lease?->real_estate_unit_id,
                'real_estate_utility_type_id' => $utilityType->id,
                'real_estate_meter_reading_id' => $data['real_estate_meter_reading_id'] ?? null,
                'invoice_id' => $invoice->id,
                'bill_number' => $data['bill_number'] ?? $this->numbers->next('real_estate_utility_bills', 'bill_number', 'UTL'),
                'period_start' => $data['period_start'] ?? now()->startOfMonth()->toDateString(),
                'period_end' => $data['period_end'] ?? now()->endOfMonth()->toDateString(),
                'quantity' => $data['quantity'] ?? 1,
                'rate_per_unit' => $data['rate_per_unit'] ?? $utilityType->default_rate,
                'fixed_charge' => $data['fixed_charge'] ?? 0,
                'amount' => $amount,
                'due_date' => $data['due_date'] ?? now()->addDays(7)->toDateString(),
                'status' => 'Outstanding',
            ]);

            $this->invoiceProfiles->apply($invoice, $tenant, $lease, $bill, 'Utility');

            UtilityInvoice::create([
                'invoice_id' => $invoice->id,
                'real_estate_tenant_id' => $tenant->id,
                'invoice_type' => 'Utility',
                'total' => $amount,
            ]);

            $this->ledger->recordCharge($tenant, $bill, 'Utility Charge', $utilityType->name.' utility bill '.$bill->bill_number, $amount, [
                'real_estate_lease_id' => $lease?->id,
                'real_estate_property_id' => $bill->real_estate_property_id,
                'real_estate_unit_id' => $bill->real_estate_unit_id,
                'invoice_id' => $invoice->id,
                'entry_date' => $bill->period_end,
            ]);

            return $bill;
        });
    }

    public function createAmenity(array $data): Amenity
    {
        return Amenity::create($data);
    }

    public function createAmenityBooking(array $data): AmenityBooking
    {
        return DB::transaction(function () use ($data) {
            $amenity = Amenity::findOrFail($data['real_estate_amenity_id']);
            $tenant = Tenant::with('client')->findOrFail($data['real_estate_tenant_id']);
            $amount = round((float) ($data['charge_amount'] ?? $amenity->fee_amount), 2);
            $invoice = $amount > 0 ? $this->invoice($tenant, 'Amenity - '.$amenity->name, $amount, $data['booking_date'] ?? now()->toDateString()) : null;

            $booking = AmenityBooking::create([
                'real_estate_amenity_id' => $amenity->id,
                'real_estate_tenant_id' => $tenant->id,
                'real_estate_unit_id' => $data['real_estate_unit_id'] ?? null,
                'invoice_id' => $invoice?->id,
                'booking_number' => $data['booking_number'] ?? $this->numbers->next('real_estate_amenity_bookings', 'booking_number', 'AMN'),
                'booking_date' => $data['booking_date'] ?? now()->toDateString(),
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'charge_amount' => $amount,
                'status' => $data['status'] ?? 'Pending',
                'notes' => $data['notes'] ?? null,
            ]);

            if ($invoice) {
                $lease = Lease::where('real_estate_tenant_id', $tenant->id)
                    ->where('status', 'Active')
                    ->latest('id')
                    ->first();
                $this->invoiceProfiles->apply($invoice, $tenant, $lease, $booking, 'Amenity');

                $this->ledger->recordCharge($tenant, $booking, 'Amenity Charge', $amenity->name.' amenity booking '.$booking->booking_number, $amount, [
                    'real_estate_unit_id' => $booking->real_estate_unit_id,
                    'invoice_id' => $invoice->id,
                    'entry_date' => $booking->booking_date,
                ]);
            }

            return $booking;
        });
    }

    private function invoice(Tenant $tenant, string $description, float $amount, ?string $dueDate): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $tenant->client_id,
            'invoice_number' => $this->documents->number('invoice'),
            'public_token' => Str::random(48),
            'invoice_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'payment_status' => 'unpaid',
            'subtotal' => $amount,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => $amount,
            'amount_paid' => 0,
            'balance' => $amount,
            'notes' => $description.' generated by Real Estate billing.',
        ]);

        $invoice->items()->create([
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $amount,
            'discount' => 0,
            'tax_rate' => 0,
            'line_total' => $amount,
        ]);

        return $invoice;
    }
}
