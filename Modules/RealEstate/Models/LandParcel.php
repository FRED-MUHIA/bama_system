<?php

namespace Modules\RealEstate\Models;

class LandParcel extends RealEstateModel
{
    protected $table = 'real_estate_land_parcels';
    protected $casts = ['land_size' => 'decimal:4', 'ownership_history' => 'array', 'sales_history' => 'array'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
}
