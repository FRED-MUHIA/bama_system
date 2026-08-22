<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\User;

class RetailSalesExtension extends RetailModel
{
    protected $casts = [
        'split_payment_summary' => 'array',
        'voided_at' => 'datetime',
        'layaway_due_at' => 'date',
    ];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function cashDrawer() { return $this->belongsTo(RetailCashDrawer::class, 'retail_cash_drawer_id'); }
    public function promotion() { return $this->belongsTo(RetailPromotion::class, 'retail_promotion_id'); }
}
