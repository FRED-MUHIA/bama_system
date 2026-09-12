<?php

namespace Modules\Chama\Models;

class InvestmentIncome extends ChamaModel
{
    protected $table = 'chama_investment_income';

    protected $casts = [
        'income_date' => 'date',
    ];

    public function investment() { return $this->belongsTo(Investment::class); }
}
