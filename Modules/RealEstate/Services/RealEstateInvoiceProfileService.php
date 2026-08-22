<?php

namespace Modules\RealEstate\Services;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Support\ActiveBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Modules\RealEstate\Models\AmenityBooking;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\UtilityBill;

class RealEstateInvoiceProfileService
{
    public function __construct(private RealEstateNumberService $numbers) {}

    public function apply(Invoice $invoice, Tenant $tenant, ?Lease $lease = null, ?Model $source = null, ?string $category = null): Invoice
    {
        if (! Schema::hasColumn('invoices', 'industry_reference')) {
            return $invoice;
        }

        $tenant->loadMissing('client');
        $lease?->loadMissing('property', 'unit');
        if ($source instanceof AmenityBooking) {
            $source->loadMissing('unit.property');
        }
        if ($source instanceof UtilityBill) {
            $source->loadMissing('property', 'unit.property');
        }
        $property = $lease?->property ?? $source?->property ?? $source?->unit?->property;
        $unit = $lease?->unit ?? $source?->unit;
        $business = ActiveBusiness::current();
        $settings = CompanySetting::withoutGlobalScopes()
            ->where('business_id', $invoice->business_id ?: $business?->id)
            ->first();

        $invoice->forceFill([
            'industry_module' => 'real_estate',
            'industry_reference' => $invoice->industry_reference ?: $this->numbers->next('invoices', 'industry_reference', 'REI'),
            'issuer_profile' => [
                'name' => $settings?->company_name ?: $business?->name,
                'subtitle' => 'Real Estate Management',
                'phone' => $settings?->phone,
                'email' => $settings?->email,
                'address' => $settings?->address,
                'website' => $settings?->website,
                'tax_name' => $settings?->tax_name,
            ],
            'recipient_profile' => [
                'client_id' => $tenant->client_id,
                'tenant_id' => $tenant->id,
                'tenant_number' => $tenant->tenant_number,
                'name' => $tenant->client?->name,
                'phone' => $tenant->client?->phone,
                'email' => $tenant->client?->email,
                'address' => $tenant->client?->address,
                'id_number' => $tenant->id_number,
                'passport_number' => $tenant->passport_number,
            ],
            'industry_context' => [
                'category' => $category,
                'lease_number' => $lease?->lease_number,
                'property_code' => $property?->property_code,
                'property_name' => $property?->property_name,
                'unit_number' => $unit?->unit_number,
                'unit_type' => $unit?->unit_type,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'source_reference' => $this->sourceReference($source),
            ],
        ])->save();

        return $invoice;
    }

    private function sourceReference(?Model $source): ?string
    {
        if (! $source) {
            return null;
        }

        foreach (['charge_number', 'bill_number', 'booking_number', 'sale_number'] as $field) {
            if (isset($source->{$field})) {
                return $source->{$field};
            }
        }

        return null;
    }
}
