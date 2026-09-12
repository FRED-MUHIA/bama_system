<?php

namespace Modules\Chama\Models;

class Investment extends ChamaModel
{
    protected $table = 'chama_investments';

    protected $casts = [
        'purchase_date' => 'date',
        'ownership_allocations' => 'array',
        'documents' => 'array',
    ];

    public function incomeRecords() { return $this->hasMany(InvestmentIncome::class); }
}
