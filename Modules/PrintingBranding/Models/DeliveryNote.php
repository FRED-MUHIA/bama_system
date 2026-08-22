<?php

namespace Modules\PrintingBranding\Models;

class DeliveryNote extends PrintingBrandingModel
{
    protected $table = 'printing_delivery_notes';

    protected $casts = [
        'products' => 'array',
        'delivery_date' => 'date',
    ];
}
