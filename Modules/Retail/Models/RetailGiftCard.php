<?php

namespace Modules\Retail\Models;

use App\Models\Client;

class RetailGiftCard extends RetailModel
{
    protected $casts = [
        'issued_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'expires_at' => 'date',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function transactions() { return $this->hasMany(RetailGiftCardTransaction::class); }
}
