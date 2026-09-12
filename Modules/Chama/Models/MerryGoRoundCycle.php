<?php

namespace Modules\Chama\Models;

class MerryGoRoundCycle extends ChamaModel
{
    protected $table = 'chama_merry_go_round_cycles';

    protected $casts = [
        'contribution_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'start_date' => 'date',
        'next_payout_date' => 'date',
        'settings' => 'array',
    ];

    public function members() { return $this->hasMany(MerryGoRoundMember::class, 'cycle_id'); }
    public function rounds() { return $this->hasMany(MerryGoRoundRound::class, 'cycle_id'); }
}
