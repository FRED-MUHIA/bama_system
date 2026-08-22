<?php

namespace Modules\Retail\Models;

use App\Models\Supplier;

class RetailSupplierProfile extends RetailModel
{
    protected $casts = [
        'lead_time_days' => 'integer',
        'delivery_accuracy' => 'decimal:2',
        'rating' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
}
