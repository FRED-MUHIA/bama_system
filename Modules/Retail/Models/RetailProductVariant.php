<?php

namespace Modules\Retail\Models;

use App\Models\Product;

class RetailProductVariant extends RetailModel
{
    protected $casts = [
        'attributes' => 'array',
        'price_delta' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'promotional_price' => 'decimal:2',
        'weight' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
    ];

    public function parentProduct() { return $this->belongsTo(Product::class, 'parent_product_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function attributeValueLinks() { return $this->hasMany(ProductVariantAttributeValue::class, 'variant_id'); }
    public function attributeValues() { return $this->belongsToMany(\App\Models\ProductAttributeValue::class, 'product_variant_attribute_values', 'variant_id', 'product_attribute_value_id')->withPivot('product_attribute_id')->withTimestamps(); }
    public function inventoryBalances() { return $this->hasMany(RetailInventoryBalance::class, 'retail_product_variant_id'); }
    public function serials() { return $this->hasMany(ProductSerial::class, 'retail_product_variant_id'); }

    public function displayName(): string
    {
        return $this->variant_name ?: collect($this->attributes ?? [])->filter()->values()->implode(' / ');
    }
}
