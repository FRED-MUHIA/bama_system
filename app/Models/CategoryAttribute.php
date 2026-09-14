<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class CategoryAttribute extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'is_variant_attribute' => 'boolean',
        'metadata' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
