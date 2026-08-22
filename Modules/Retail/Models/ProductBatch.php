<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;

class ProductBatch extends RetailModel
{
    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
        'sold_quantity' => 'decimal:3',
        'recalled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function movements() { return $this->hasMany(ProductBatchMovement::class); }
}
