<?php

namespace Modules\Retail\Models;

class RetailPromotion extends RetailModel
{
    public const TYPES = ['Percentage Discount', 'Fixed Discount', 'Buy One Get One', 'Bundle Discount', 'Flash Sale', 'Happy Hour Pricing', 'Seasonal Promotion'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'discount_value' => 'decimal:2',
        'product_eligibility' => 'array',
        'customer_eligibility' => 'array',
        'store_eligibility' => 'array',
        'metadata' => 'array',
    ];
}
