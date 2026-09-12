<?php

namespace Modules\Chama\Models;

class Contribution extends ChamaModel
{
    protected $table = 'chama_contributions';

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function member() { return $this->belongsTo(Member::class); }
    public function contributionType() { return $this->belongsTo(ContributionType::class); }
    public function schedule() { return $this->belongsTo(ContributionSchedule::class, 'contribution_schedule_id'); }
}
