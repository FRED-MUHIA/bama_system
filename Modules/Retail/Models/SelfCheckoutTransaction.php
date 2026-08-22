<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Client;
use App\Models\PosOrder;

class SelfCheckoutTransaction extends RetailModel
{
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'cart_payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public function device() { return $this->belongsTo(ScanDevice::class, 'scan_device_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
}
