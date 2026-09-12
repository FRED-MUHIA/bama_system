<?php

namespace Modules\Chama\Models;

class ContributionSchedule extends ChamaModel
{
    protected $table = 'chama_contribution_schedules';

    protected $casts = [
        'due_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'penalty' => 'decimal:2',
        'balance' => 'decimal:2',
        'waived_at' => 'datetime',
    ];

    public function member() { return $this->belongsTo(Member::class); }
    public function contributionType() { return $this->belongsTo(ContributionType::class); }
    public function contributions() { return $this->hasMany(Contribution::class); }
}
