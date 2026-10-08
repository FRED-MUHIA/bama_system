<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class PosOrderPayment extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'pos_order_id', 'payment_method_id', 'amount', 'payment_date', 'reference', 'notes', 'method_type', 'retail_gift_card_id',
    ];

    protected $casts = ['payment_date' => 'date'];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function paymentMethod() { return $this->belongsTo(PaymentMethod::class); }
    public function giftCard() { return $this->belongsTo(\Modules\Retail\Models\RetailGiftCard::class, 'retail_gift_card_id'); }
}
