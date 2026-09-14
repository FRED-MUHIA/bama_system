<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Modules\Retail\Models\RetailProductVariant;

class ProductSupplierItem extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    protected $casts = [
        'supplier_cost' => 'decimal:2',
        'minimum_order_quantity' => 'decimal:3',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(RetailProductVariant::class, 'retail_product_variant_id');
    }
}
