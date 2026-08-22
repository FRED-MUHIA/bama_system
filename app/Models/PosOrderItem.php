<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    protected $fillable = [
        'pos_order_id', 'product_id', 'title', 'description', 'quantity', 'unit_price',
        'discount', 'tax_rate', 'line_total',
    ];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
