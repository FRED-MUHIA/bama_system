<?php

namespace Modules\RealEstate\Models;

use App\Models\Client;

class Buyer extends RealEstateModel
{
    protected $table = 'real_estate_buyers';
    protected $casts = ['budget' => 'decimal:2'];

    public function client() { return $this->belongsTo(Client::class); }
    public function sales() { return $this->hasMany(Sale::class, 'real_estate_buyer_id'); }
}
