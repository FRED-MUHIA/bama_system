<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\User;

class ScanEvent extends RetailModel
{
    protected $casts = [
        'quantity' => 'decimal:3',
        'before_quantity' => 'decimal:3',
        'sold_quantity' => 'decimal:3',
        'remaining_quantity' => 'decimal:3',
        'original_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'decoded_payload' => 'array',
        'promotion_payload' => 'array',
        'compliance_payload' => 'array',
        'scanned_at' => 'datetime',
    ];

    public function device() { return $this->belongsTo(ScanDevice::class, 'scan_device_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function verification() { return $this->hasOne(ProductVerification::class); }
}
