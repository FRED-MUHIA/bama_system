<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class GoodsReceivedNote extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'purchase_order_id', 'product_id', 'received_date', 'quantity_received', 'unit_cost', 'line_total', 'received_by', 'notes'];
    protected $casts = ['received_date' => 'date', 'quantity_received' => 'decimal:3', 'unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
