<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'industry_context')) {
            return;
        }

        $this->refreshAmenityBookings();
        $this->refreshUtilityBills();
    }

    public function down(): void
    {
        //
    }

    private function refreshAmenityBookings(): void
    {
        if (! Schema::hasTable('real_estate_amenity_bookings')) {
            return;
        }

        DB::table('real_estate_amenity_bookings as booking')
            ->leftJoin('real_estate_units as unit', 'unit.id', '=', 'booking.real_estate_unit_id')
            ->leftJoin('real_estate_properties as property', 'property.id', '=', 'unit.real_estate_property_id')
            ->whereNotNull('booking.invoice_id')
            ->select([
                'booking.invoice_id',
                'unit.unit_number',
                'unit.unit_type',
                'property.property_code',
                'property.property_name',
            ])
            ->orderBy('booking.invoice_id')
            ->each(fn ($row) => $this->mergeContext($row));
    }

    private function refreshUtilityBills(): void
    {
        if (! Schema::hasTable('real_estate_utility_bills')) {
            return;
        }

        DB::table('real_estate_utility_bills as bill')
            ->leftJoin('real_estate_units as unit', 'unit.id', '=', 'bill.real_estate_unit_id')
            ->leftJoin('real_estate_properties as bill_property', 'bill_property.id', '=', 'bill.real_estate_property_id')
            ->leftJoin('real_estate_properties as unit_property', 'unit_property.id', '=', 'unit.real_estate_property_id')
            ->whereNotNull('bill.invoice_id')
            ->select([
                'bill.invoice_id',
                'unit.unit_number',
                'unit.unit_type',
                DB::raw('COALESCE(bill_property.property_code, unit_property.property_code) as property_code'),
                DB::raw('COALESCE(bill_property.property_name, unit_property.property_name) as property_name'),
            ])
            ->orderBy('bill.invoice_id')
            ->each(fn ($row) => $this->mergeContext($row));
    }

    private function mergeContext(object $row): void
    {
        $invoice = DB::table('invoices')
            ->where('id', $row->invoice_id)
            ->where('industry_module', 'real_estate')
            ->first(['id', 'industry_context']);

        if (! $invoice) {
            return;
        }

        $context = json_decode((string) $invoice->industry_context, true) ?: [];
        $context['property_code'] = $context['property_code'] ?? $row->property_code;
        $context['property_name'] = $context['property_name'] ?? $row->property_name;
        $context['unit_number'] = $context['unit_number'] ?? $row->unit_number;
        $context['unit_type'] = $context['unit_type'] ?? $row->unit_type;

        DB::table('invoices')->where('id', $invoice->id)->update([
            'industry_context' => json_encode($context),
            'updated_at' => now(),
        ]);
    }
};
