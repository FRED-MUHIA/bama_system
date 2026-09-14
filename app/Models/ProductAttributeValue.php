<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttributeValue extends Model
{
    use BelongsToBusiness;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'numeric_value' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
