<?php

namespace Modules\Retail\Models;

use App\Models\Client;

class RetailLoyaltyAccount extends RetailModel
{
    public const TIERS = ['Bronze', 'Silver', 'Gold', 'Platinum'];

    protected $casts = [
        'points_balance' => 'integer',
        'points_earned' => 'integer',
        'points_redeemed' => 'integer',
        'cashback_balance' => 'decimal:2',
        'joined_at' => 'date',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function transactions() { return $this->hasMany(RetailLoyaltyTransaction::class); }
}
