<?php

namespace Modules\Retail\Models;

class RetailEcommerceIntegration extends RetailModel
{
    protected $casts = [
        'last_product_sync_at' => 'datetime',
        'last_inventory_sync_at' => 'datetime',
        'last_order_sync_at' => 'datetime',
        'last_customer_sync_at' => 'datetime',
        'settings' => 'array',
    ];
}
