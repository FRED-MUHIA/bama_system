<?php

namespace Modules\Chama\Models;

class LoanProduct extends ChamaModel
{
    protected $table = 'chama_loan_products';

    protected $casts = [
        'interest_rate' => 'decimal:4',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'eligibility_rules' => 'array',
        'repayment_allocation_order' => 'array',
        'is_active' => 'boolean',
    ];

    public function loans() { return $this->hasMany(Loan::class); }
}
