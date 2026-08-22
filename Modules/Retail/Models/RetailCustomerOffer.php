<?php

namespace Modules\Retail\Models;

use App\Models\Client;

class RetailCustomerOffer extends RetailModel
{
    protected $casts = [
        'behavior_summary' => 'array',
        'recommended_products' => 'array',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function promotion() { return $this->belongsTo(RetailPromotion::class, 'retail_promotion_id'); }
}
