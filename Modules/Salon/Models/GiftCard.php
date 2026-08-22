<?php

namespace Modules\Salon\Models;

use App\Models\Client;

class GiftCard extends SalonModel
{
    protected $table = 'salon_gift_cards';

    protected $casts = [
        'initial_value' => 'decimal:2',
        'balance' => 'decimal:2',
        'expires_on' => 'date',
        'transactions' => 'array',
    ];

    public function client() { return $this->belongsTo(Client::class); }
}
