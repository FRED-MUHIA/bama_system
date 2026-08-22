<?php

namespace Modules\RealEstate\Models;

class UtilityRate extends RealEstateModel
{
    protected $table = 'real_estate_utility_rates';
    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'rate_per_unit' => 'decimal:4', 'fixed_charge' => 'decimal:2', 'minimum_charge' => 'decimal:2', 'is_active' => 'boolean'];

    public function utilityType() { return $this->belongsTo(UtilityType::class, 'real_estate_utility_type_id'); }
    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
}
