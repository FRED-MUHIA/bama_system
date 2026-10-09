<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\User;

class RetailExpense extends RetailModel
{
    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'created_by'); }
}
