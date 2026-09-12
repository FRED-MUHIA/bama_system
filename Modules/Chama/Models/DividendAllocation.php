<?php

namespace Modules\Chama\Models;

class DividendAllocation extends ChamaModel
{
    protected $table = 'chama_dividend_allocations';

    protected $casts = [
        'paid_at' => 'date',
    ];

    public function dividendRun() { return $this->belongsTo(DividendRun::class); }
    public function member() { return $this->belongsTo(Member::class); }
}
