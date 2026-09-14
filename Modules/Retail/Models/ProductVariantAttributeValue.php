<?php

namespace Modules\Retail\Models;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;

class ProductVariantAttributeValue extends RetailModel
{
    protected $table = 'product_variant_attribute_values';

    public function variant()
    {
        return $this->belongsTo(RetailProductVariant::class, 'variant_id');
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function value()
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
