<?php

namespace Modules\Retail\Models;

use App\Models\Client;
use App\Models\PosOrder;
use App\Models\Product;

class ProductSerial extends RetailModel
{
    protected $casts = [
        'warranty_starts_at' => 'date',
        'warranty_ends_at' => 'date',
        'metadata' => 'array',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(RetailProductVariant::class, 'retail_product_variant_id'); }
    public function batch() { return $this->belongsTo(ProductBatch::class, 'product_batch_id'); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function customer() { return $this->belongsTo(Client::class, 'client_id'); }
}
