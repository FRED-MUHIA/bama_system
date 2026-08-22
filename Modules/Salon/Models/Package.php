<?php

namespace Modules\Salon\Models;

class Package extends SalonModel
{
    protected $table = 'salon_packages';

    protected $casts = [
        'price' => 'decimal:2',
        'service_ids' => 'array',
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];
}
