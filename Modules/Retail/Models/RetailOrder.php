<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Client;
use App\Models\PosOrder;

class RetailOrder extends RetailModel
{
    public const STATUSES = ['Draft', 'Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered', 'Cancelled'];

    protected $casts = [
        'order_date' => 'date',
        'requested_delivery_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function posOrder() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function items() { return $this->hasMany(RetailOrderItem::class); }
    public function delivery() { return $this->hasOne(RetailDelivery::class); }
    public function fulfillment() { return $this->hasOne(RetailOrderFulfillment::class); }
}
