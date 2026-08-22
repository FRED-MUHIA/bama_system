<?php

namespace Modules\RealEstate\Models;

use App\Models\User;

class Valuation extends RealEstateModel
{
    protected $table = 'real_estate_valuations';
    protected $casts = ['valuation_date' => 'date', 'market_value' => 'decimal:2', 'rental_value' => 'decimal:2'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function valuer() { return $this->belongsTo(User::class, 'valuer_id'); }
}
