<?php

namespace Modules\Retail\Models;

use App\Models\User;

class RetailDelivery extends RetailModel
{
    public const STATUSES = ['Scheduled', 'In Transit', 'Delivered', 'Failed'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'route_plan' => 'array',
        'tracking_events' => 'array',
    ];

    public function order() { return $this->belongsTo(RetailOrder::class, 'retail_order_id'); }
    public function driver() { return $this->belongsTo(User::class, 'driver_id'); }
}
