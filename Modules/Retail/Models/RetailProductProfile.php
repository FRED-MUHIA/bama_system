<?php

namespace Modules\Retail\Models;

use App\Models\Product;
use App\Models\Supplier;

class RetailProductProfile extends RetailModel
{
    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'tags' => 'array',
        'localized_content' => 'array',
        'currency_prices' => 'array',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
