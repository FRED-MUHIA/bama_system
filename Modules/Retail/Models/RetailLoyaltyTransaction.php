<?php

namespace Modules\Retail\Models;

use App\Models\PosOrder;

class RetailLoyaltyTransaction extends RetailModel
{
    protected $casts = ['points' => 'integer', 'amount' => 'decimal:2'];

    public function account() { return $this->belongsTo(RetailLoyaltyAccount::class, 'retail_loyalty_account_id'); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
}
