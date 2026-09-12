<?php

namespace Modules\Chama\Models;

class SavingsAccount extends ChamaModel
{
    protected $table = 'chama_savings_accounts';

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'settings' => 'array',
    ];

    public function member() { return $this->belongsTo(Member::class); }
    public function transactions() { return $this->hasMany(SavingsTransaction::class); }
}
