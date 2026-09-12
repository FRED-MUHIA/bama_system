<?php

namespace Modules\Chama\Models;

class MerryGoRoundRound extends ChamaModel
{
    protected $table = 'chama_merry_go_round_rounds';

    protected $casts = [
        'due_date' => 'date',
        'payout_date' => 'date',
        'approved_at' => 'datetime',
        'expected_amount' => 'decimal:2',
        'amount_collected' => 'decimal:2',
        'payout_amount' => 'decimal:2',
    ];

    public function cycle() { return $this->belongsTo(MerryGoRoundCycle::class, 'cycle_id'); }
    public function beneficiary() { return $this->belongsTo(Member::class, 'beneficiary_id'); }
}
