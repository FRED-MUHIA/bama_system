<?php

namespace Modules\RealEstate\Models;

use App\Models\User;

class Inspection extends RealEstateModel
{
    protected $table = 'real_estate_inspections';
    protected $casts = ['inspection_date' => 'date', 'photos' => 'array'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function inspector() { return $this->belongsTo(User::class, 'inspector_id'); }
}
