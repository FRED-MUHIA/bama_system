<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;

class RetailCycleCount extends RetailModel
{
    protected $casts = [
        'scheduled_at' => 'datetime',
        'counted_at' => 'datetime',
        'system_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function bin() { return $this->belongsTo(RetailWarehouseBin::class, 'retail_warehouse_bin_id'); }
    public function countedBy() { return $this->belongsTo(User::class, 'counted_by'); }
}
