<?php

namespace Modules\Chama\Models;

class Minute extends ChamaModel
{
    protected $table = 'chama_minutes';

    protected $casts = [
        'financial_summary' => 'array',
        'loan_approvals' => 'array',
        'investment_decisions' => 'array',
        'approved_at' => 'datetime',
    ];

    public function meeting() { return $this->belongsTo(Meeting::class); }
}
