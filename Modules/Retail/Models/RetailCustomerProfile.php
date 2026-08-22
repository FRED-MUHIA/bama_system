<?php

namespace Modules\Retail\Models;

use App\Models\Client;

class RetailCustomerProfile extends RetailModel
{
    protected $casts = [
        'shopping_preferences' => 'array',
        'lifetime_value' => 'decimal:2',
        'total_purchases' => 'integer',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function loyaltyAccount() { return $this->hasOne(RetailLoyaltyAccount::class, 'client_id', 'client_id'); }
}
