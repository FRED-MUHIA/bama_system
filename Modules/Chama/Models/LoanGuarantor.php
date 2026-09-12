<?php

namespace Modules\Chama\Models;

class LoanGuarantor extends ChamaModel
{
    protected $table = 'chama_loan_guarantors';

    protected $casts = [
        'confirmed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function loan() { return $this->belongsTo(Loan::class); }
    public function guarantor() { return $this->belongsTo(Member::class, 'guarantor_member_id'); }
}
