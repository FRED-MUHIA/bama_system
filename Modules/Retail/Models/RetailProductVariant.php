<?php

namespace Modules\Retail\Models;

use App\Models\Product;

class RetailProductVariant extends RetailModel
{
    protected $casts = ['attributes' => 'array'];

    public function parentProduct() { return $this->belongsTo(Product::class, 'parent_product_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
