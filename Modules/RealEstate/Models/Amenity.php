<?php

namespace Modules\RealEstate\Models;

class Amenity extends RealEstateModel
{
    protected $table = 'real_estate_amenities';
    protected $casts = ['capacity' => 'integer', 'fee_amount' => 'decimal:2', 'is_active' => 'boolean'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function bookings() { return $this->hasMany(AmenityBooking::class, 'real_estate_amenity_id'); }
}
