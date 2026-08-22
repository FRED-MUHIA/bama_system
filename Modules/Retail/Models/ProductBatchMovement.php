<?php

namespace Modules\Retail\Models;

class ProductBatchMovement extends RetailModel
{
    protected $casts = ['quantity' => 'decimal:3', 'balance_after' => 'decimal:3'];

    public function batch() { return $this->belongsTo(ProductBatch::class, 'product_batch_id'); }
    public function scanEvent() { return $this->belongsTo(ScanEvent::class); }
}
