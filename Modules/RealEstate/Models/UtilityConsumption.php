<?php

namespace Modules\RealEstate\Models;

class UtilityConsumption extends RealEstateModel
{
    protected $table = 'real_estate_utility_consumption';
    protected $casts = ['consumption_date' => 'date', 'quantity' => 'decimal:4', 'amount' => 'decimal:2', 'metadata' => 'array'];

    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function utilityType() { return $this->belongsTo(UtilityType::class, 'real_estate_utility_type_id'); }
    public function meterReading() { return $this->belongsTo(MeterReading::class, 'real_estate_meter_reading_id'); }
}
