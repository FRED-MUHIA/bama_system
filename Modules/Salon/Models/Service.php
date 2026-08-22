<?php

namespace Modules\Salon\Models;

class Service extends SalonModel
{
    protected $table = 'salon_services';

    protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'requires_consultation' => 'boolean',
        'is_package_component' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function appointmentServices() { return $this->hasMany(AppointmentService::class, 'salon_service_id'); }
}
