<?php

namespace Modules\Chama\Models;

class LoanRepayment extends ChamaModel
{
    protected $table = 'chama_loan_repayments';

    protected $casts = [
        'payment_date' => 'date',
        'allocation' => 'array',
    ];

    public function loan() { return $this->belongsTo(Loan::class); }
    public function schedule() { return $this->belongsTo(LoanSchedule::class, 'loan_schedule_id'); }
    public function member() { return $this->belongsTo(Member::class); }
}
