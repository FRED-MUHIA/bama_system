<?php

namespace Modules\Chama\Models;

class SavingsTransaction extends ChamaModel
{
    protected $table = 'chama_savings_transactions';

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function savingsAccount() { return $this->belongsTo(SavingsAccount::class); }
    public function member() { return $this->belongsTo(Member::class); }
}
