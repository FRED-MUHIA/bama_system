<?php

namespace Modules\Retail\Models;

use App\Models\Product;

class RetailProductBundle extends RetailModel
{
    protected $casts = ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2'];

    public function bundleProduct() { return $this->belongsTo(Product::class, 'bundle_product_id'); }
    public function componentProduct() { return $this->belongsTo(Product::class, 'component_product_id'); }
}
