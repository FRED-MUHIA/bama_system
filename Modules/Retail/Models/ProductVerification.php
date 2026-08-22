<?php

namespace Modules\Retail\Models;

use App\Models\Product;

class ProductVerification extends RetailModel
{
    protected $table = 'product_verification';

    protected $casts = [
        'product_exists' => 'boolean',
        'product_active' => 'boolean',
        'batch_valid' => 'boolean',
        'not_recalled' => 'boolean',
        'fraud_suspected' => 'boolean',
        'checks' => 'array',
    ];

    public function scanEvent() { return $this->belongsTo(ScanEvent::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function batch() { return $this->belongsTo(ProductBatch::class, 'product_batch_id'); }
}
