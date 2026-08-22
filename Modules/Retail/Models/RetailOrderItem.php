<?php

namespace Modules\Retail\Models;

use App\Models\Product;

class RetailOrderItem extends RetailModel
{
    protected $casts = ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'discount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function order() { return $this->belongsTo(RetailOrder::class, 'retail_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
