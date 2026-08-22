<?php

namespace Modules\Retail\Models;

class RetailTaxJurisdiction extends RetailModel
{
    protected $casts = [
        'tax_rate' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
