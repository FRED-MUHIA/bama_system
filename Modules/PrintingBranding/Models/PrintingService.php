<?php

namespace Modules\PrintingBranding\Models;

class PrintingService extends PrintingBrandingModel
{
    protected $table = 'printing_services';

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
