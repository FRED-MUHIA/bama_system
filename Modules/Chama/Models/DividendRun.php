<?php

namespace Modules\Chama\Models;

class DividendRun extends ChamaModel
{
    protected $table = 'chama_dividend_runs';

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function allocations() { return $this->hasMany(DividendAllocation::class); }
}
