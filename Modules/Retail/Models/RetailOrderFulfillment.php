<?php

namespace Modules\Retail\Models;

use App\Models\Branch;

class RetailOrderFulfillment extends RetailModel
{
    protected $casts = [
        'routed_at' => 'datetime',
        'picked_at' => 'datetime',
        'packed_at' => 'datetime',
        'ready_for_pickup_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order() { return $this->belongsTo(RetailOrder::class, 'retail_order_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
}
