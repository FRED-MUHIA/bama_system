<?php

namespace Modules\Retail\Models;

use App\Models\Client;
use App\Models\PosOrder;

class RetailReturnAuthorization extends RetailModel
{
    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'refund_total' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(RetailReturnItem::class); }
}
