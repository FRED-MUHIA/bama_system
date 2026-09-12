<?php

namespace Modules\Chama\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends ChamaModel
{
    use SoftDeletes;

    protected $table = 'chama_loans';

    protected $casts = [
        'application_date' => 'date',
        'disbursement_date' => 'date',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'eligibility_result' => 'array',
    ];

    public function product() { return $this->belongsTo(LoanProduct::class, 'loan_product_id'); }
    public function member() { return $this->belongsTo(Member::class); }
    public function guarantors() { return $this->hasMany(LoanGuarantor::class); }
    public function schedules() { return $this->hasMany(LoanSchedule::class); }
    public function repayments() { return $this->hasMany(LoanRepayment::class); }
}
