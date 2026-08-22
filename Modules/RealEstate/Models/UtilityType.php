<?php

namespace Modules\RealEstate\Models;

class UtilityType extends RealEstateModel
{
    protected $table = 'real_estate_utility_types';
    protected $casts = ['default_rate' => 'decimal:4', 'is_custom' => 'boolean', 'is_active' => 'boolean'];

    public function meters() { return $this->hasMany(UtilityMeter::class, 'real_estate_utility_type_id'); }
    public function bills() { return $this->hasMany(UtilityBill::class, 'real_estate_utility_type_id'); }
}
