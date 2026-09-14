<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeAssignment extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    protected $casts = [
        'is_variant_attribute' => 'boolean',
        'metadata' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
