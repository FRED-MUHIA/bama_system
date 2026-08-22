<?php

namespace Modules\RealEstate\Models;

class Listing extends RealEstateModel
{
    protected $table = 'real_estate_listings';
    protected $casts = ['listing_date' => 'date', 'expiry_date' => 'date', 'price' => 'decimal:2', 'is_featured' => 'boolean', 'public_ready' => 'boolean', 'images' => 'array', 'features' => 'array'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function agent() { return $this->belongsTo(Agent::class, 'real_estate_agent_id'); }
}
