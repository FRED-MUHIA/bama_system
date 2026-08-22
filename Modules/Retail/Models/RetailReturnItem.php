<?php

namespace Modules\Retail\Models;

use App\Models\PosOrderItem;
use App\Models\Product;

class RetailReturnItem extends RetailModel
{
    protected $casts = ['quantity' => 'decimal:3', 'refund_amount' => 'decimal:2'];

    public function authorization() { return $this->belongsTo(RetailReturnAuthorization::class, 'retail_return_authorization_id'); }
    public function orderItem() { return $this->belongsTo(PosOrderItem::class, 'pos_order_item_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
