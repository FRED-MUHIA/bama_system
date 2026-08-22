<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Product;

class RetailInventoryBalance extends RetailModel
{
    protected $casts = [
        'available_stock' => 'decimal:3',
        'reserved_stock' => 'decimal:3',
        'in_transit_stock' => 'decimal:3',
        'damaged_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'stock_value' => 'decimal:2',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function bin() { return $this->belongsTo(RetailWarehouseBin::class, 'retail_warehouse_bin_id'); }
}
