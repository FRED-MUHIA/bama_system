<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Product;

class RetailInventoryMovement extends RetailModel
{
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'balance_after' => 'decimal:3',
        'metadata' => 'array',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function bin() { return $this->belongsTo(RetailWarehouseBin::class, 'retail_warehouse_bin_id'); }
    public function source() { return $this->morphTo(); }
}
