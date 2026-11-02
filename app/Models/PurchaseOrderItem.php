<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'purchase_order_id', 'description', 'quantity', 'unit_price', 'tax_rate', 'tax_amount', 'line_total'];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
}
