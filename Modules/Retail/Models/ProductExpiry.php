<?php

namespace Modules\Retail\Models;

use App\Models\Product;

class ProductExpiry extends RetailModel
{
    protected $table = 'product_expiry';

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'checked_at' => 'datetime',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function batch() { return $this->belongsTo(ProductBatch::class, 'product_batch_id'); }
}
