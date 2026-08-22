<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\User;

class RetailCashDrawer extends RetailModel
{
    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_float' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'cash_refunds' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
}
