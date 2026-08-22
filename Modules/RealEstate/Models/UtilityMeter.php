<?php

namespace Modules\RealEstate\Models;

class UtilityMeter extends RealEstateModel
{
    protected $table = 'real_estate_utility_meters';
    protected $casts = ['previous_reading' => 'decimal:4', 'current_reading' => 'decimal:4', 'reading_date' => 'date', 'rate_per_unit' => 'decimal:4'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function utilityType() { return $this->belongsTo(UtilityType::class, 'real_estate_utility_type_id'); }
    public function readings() { return $this->hasMany(MeterReading::class, 'real_estate_utility_meter_id'); }
}
