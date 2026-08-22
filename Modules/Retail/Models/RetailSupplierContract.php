<?php

namespace Modules\Retail\Models;

use App\Models\Product;
use App\Models\Supplier;

class RetailSupplierContract extends RetailModel
{
    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'scorecard' => 'array',
        'landed_cost_components' => 'array',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
