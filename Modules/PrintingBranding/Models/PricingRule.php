<?php

namespace Modules\PrintingBranding\Models;

class PricingRule extends PrintingBrandingModel
{
    protected $table = 'printing_pricing_rules';

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];
}
