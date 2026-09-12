<?php

namespace Modules\Chama\Models;

class ContributionType extends ChamaModel
{
    protected $table = 'chama_contribution_types';

    protected $casts = [
        'amount' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'applicable_member_ids' => 'array',
    ];

    public function schedules() { return $this->hasMany(ContributionSchedule::class); }
    public function contributions() { return $this->hasMany(Contribution::class); }
}
