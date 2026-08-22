<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

class RetailReplenishmentPlan extends RetailModel
{
    protected $casts = [
        'average_daily_demand' => 'decimal:3',
        'demand_forecast_qty' => 'decimal:3',
        'safety_stock_qty' => 'decimal:3',
        'reorder_point_qty' => 'decimal:3',
        'available_stock_qty' => 'decimal:3',
        'recommended_order_qty' => 'decimal:3',
        'landed_cost_per_unit' => 'decimal:2',
        'estimated_total_cost' => 'decimal:2',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
}
