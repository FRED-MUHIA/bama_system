<?php

namespace Modules\Retail\Models;

use App\Models\PosOrder;

class RetailGiftCardTransaction extends RetailModel
{
    protected $casts = ['amount' => 'decimal:2', 'balance_after' => 'decimal:2'];

    public function giftCard() { return $this->belongsTo(RetailGiftCard::class, 'retail_gift_card_id'); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
}
