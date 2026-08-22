<?php

namespace Modules\PrintingBranding\Models;

class ProductTemplate extends PrintingBrandingModel
{
    protected $table = 'printing_product_templates';

    protected $casts = [
        'specifications' => 'array',
        'default_costing' => 'array',
        'is_active' => 'boolean',
    ];
}
