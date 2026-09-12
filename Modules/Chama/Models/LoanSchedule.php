<?php

namespace Modules\Chama\Models;

class LoanSchedule extends ChamaModel
{
    protected $table = 'chama_loan_schedules';

    protected $casts = [
        'due_date' => 'date',
    ];

    public function loan() { return $this->belongsTo(Loan::class); }
}
