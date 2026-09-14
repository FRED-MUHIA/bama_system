<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    protected $fillable = [
        'pos_order_id', 'product_id', 'retail_product_variant_id', 'title', 'description',
        'variant_description', 'sku_snapshot', 'barcode_snapshot', 'quantity', 'unit_price',
        'discount', 'tax_rate', 'line_total',
    ];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(\Modules\Retail\Models\RetailProductVariant::class, 'retail_product_variant_id'); }
}
