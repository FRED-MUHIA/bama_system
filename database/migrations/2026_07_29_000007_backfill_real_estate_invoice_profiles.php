<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'industry_reference')) {
            return;
        }

        $this->backfillRentalCharges();
        $this->backfillUtilityBills();
        $this->backfillAmenityBookings();
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'industry_module')) {
            return;
        }

        DB::table('invoices')
            ->where('industry_module', 'real_estate')
            ->update([
                'industry_module' => null,
                'industry_reference' => null,
                'issuer_profile' => null,
                'recipient_profile' => null,
                'industry_context' => null,
            ]);
    }

    private function backfillRentalCharges(): void
    {
        if (! Schema::hasTable('real_estate_rental_charges')) {
            return;
        }

        DB::table('real_estate_rental_charges as charge')
            ->join('real_estate_leases as lease', 'lease.id', '=', 'charge.real_estate_lease_id')
            ->join('real_estate_tenants as tenant', 'tenant.id', '=', 'lease.real_estate_tenant_id')
            ->join('clients as client', 'client.id', '=', 'tenant.client_id')
            ->join('real_estate_properties as property', 'property.id', '=', 'lease.real_estate_property_id')
            ->leftJoin('real_estate_units as unit', 'unit.id', '=', 'lease.real_estate_unit_id')
            ->whereNotNull('charge.invoice_id')
            ->select([
                'charge.invoice_id',
                'charge.business_id',
                'charge.charge_number as source_reference',
                'tenant.id as tenant_id',
                'tenant.client_id',
                'tenant.tenant_number',
                'tenant.id_number',
                'tenant.passport_number',
                'client.name',
                'client.phone',
                'client.email',
                'client.address',
                'lease.lease_number',
                'property.property_code',
                'property.property_name',
                'unit.unit_number',
                'unit.unit_type',
            ])
            ->orderBy('charge.invoice_id')
            ->each(fn ($row) => $this->backfillInvoice($row, 'Rent'));
    }

    private function backfillUtilityBills(): void
    {
        if (! Schema::hasTable('real_estate_utility_bills')) {
            return;
        }

        DB::table('real_estate_utility_bills as bill')
            ->join('real_estate_tenants as tenant', 'tenant.id', '=', 'bill.real_estate_tenant_id')
            ->join('clients as client', 'client.id', '=', 'tenant.client_id')
            ->leftJoin('real_estate_leases as lease', 'lease.id', '=', 'bill.real_estate_lease_id')
            ->leftJoin('real_estate_properties as property', 'property.id', '=', 'bill.real_estate_property_id')
            ->leftJoin('real_estate_units as unit', 'unit.id', '=', 'bill.real_estate_unit_id')
            ->whereNotNull('bill.invoice_id')
            ->select([
                'bill.invoice_id',
                'bill.business_id',
                'bill.bill_number as source_reference',
                'tenant.id as tenant_id',
                'tenant.client_id',
                'tenant.tenant_number',
                'tenant.id_number',
                'tenant.passport_number',
                'client.name',
                'client.phone',
                'client.email',
                'client.address',
                'lease.lease_number',
                'property.property_code',
                'property.property_name',
                'unit.unit_number',
                'unit.unit_type',
            ])
            ->orderBy('bill.invoice_id')
            ->each(fn ($row) => $this->backfillInvoice($row, 'Utility'));
    }

    private function backfillAmenityBookings(): void
    {
        if (! Schema::hasTable('real_estate_amenity_bookings')) {
            return;
        }

        DB::table('real_estate_amenity_bookings as booking')
            ->join('real_estate_tenants as tenant', 'tenant.id', '=', 'booking.real_estate_tenant_id')
            ->join('clients as client', 'client.id', '=', 'tenant.client_id')
            ->leftJoin('real_estate_leases as lease', function ($join) {
                $join->on('lease.real_estate_tenant_id', '=', 'tenant.id')
                    ->where('lease.status', '=', 'Active');
            })
            ->leftJoin('real_estate_properties as property', 'property.id', '=', 'lease.real_estate_property_id')
            ->leftJoin('real_estate_units as unit', 'unit.id', '=', 'booking.real_estate_unit_id')
            ->whereNotNull('booking.invoice_id')
            ->select([
                'booking.invoice_id',
                'booking.business_id',
                'booking.booking_number as source_reference',
                'tenant.id as tenant_id',
                'tenant.client_id',
                'tenant.tenant_number',
                'tenant.id_number',
                'tenant.passport_number',
                'client.name',
                'client.phone',
                'client.email',
                'client.address',
                'lease.lease_number',
                'property.property_code',
                'property.property_name',
                'unit.unit_number',
                'unit.unit_type',
            ])
            ->orderBy('booking.invoice_id')
            ->each(fn ($row) => $this->backfillInvoice($row, 'Amenity'));
    }

    private function backfillInvoice(object $row, string $category): void
    {
        $invoice = DB::table('invoices')->where('id', $row->invoice_id)->first(['id', 'business_id', 'industry_reference']);
        if (! $invoice || $invoice->industry_reference) {
            return;
        }

        DB::table('invoices')->where('id', $invoice->id)->update([
            'industry_module' => 'real_estate',
            'industry_reference' => $this->nextReference((int) ($invoice->business_id ?: $row->business_id)),
            'issuer_profile' => json_encode($this->issuerProfile((int) ($invoice->business_id ?: $row->business_id))),
            'recipient_profile' => json_encode([
                'client_id' => $row->client_id,
                'tenant_id' => $row->tenant_id,
                'tenant_number' => $row->tenant_number,
                'name' => $row->name,
                'phone' => $row->phone,
                'email' => $row->email,
                'address' => $row->address,
                'id_number' => $row->id_number,
                'passport_number' => $row->passport_number,
            ]),
            'industry_context' => json_encode([
                'category' => $category,
                'lease_number' => $row->lease_number,
                'property_code' => $row->property_code,
                'property_name' => $row->property_name,
                'unit_number' => $row->unit_number,
                'unit_type' => $row->unit_type,
                'source_reference' => $row->source_reference,
            ]),
            'updated_at' => now(),
        ]);
    }

    private function issuerProfile(int $businessId): array
    {
        $settings = Schema::hasTable('company_settings')
            ? DB::table('company_settings')->where('business_id', $businessId)->first()
            : null;
        $business = Schema::hasTable('businesses')
            ? DB::table('businesses')->where('id', $businessId)->first()
            : null;

        return [
            'name' => $settings?->company_name ?: $business?->name,
            'subtitle' => 'Real Estate Management',
            'phone' => $settings?->phone,
            'email' => $settings?->email,
            'address' => $settings?->address,
            'website' => $settings?->website,
            'tax_name' => $settings?->tax_name,
        ];
    }

    private function nextReference(int $businessId): string
    {
        $year = now()->format('Y');
        $base = "REI-{$year}-";
        $highest = DB::table('invoices')
            ->where('business_id', $businessId)
            ->where('industry_reference', 'like', $base.'%')
            ->pluck('industry_reference')
            ->reduce(function (int $highest, string $reference) use ($base) {
                if (! preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $reference, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return $base.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
};
