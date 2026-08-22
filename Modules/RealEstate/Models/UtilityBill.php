<?php

namespace Modules\RealEstate\Models;

use App\Models\Invoice;

class UtilityBill extends RealEstateModel
{
    protected $table = 'real_estate_utility_bills';
    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date', 'quantity' => 'decimal:4', 'rate_per_unit' => 'decimal:4', 'fixed_charge' => 'decimal:2', 'amount' => 'decimal:2'];

    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function lease() { return $this->belongsTo(Lease::class, 'real_estate_lease_id'); }
    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function utilityType() { return $this->belongsTo(UtilityType::class, 'real_estate_utility_type_id'); }
    public function meterReading() { return $this->belongsTo(MeterReading::class, 'real_estate_meter_reading_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
