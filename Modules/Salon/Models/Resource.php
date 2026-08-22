<?php

namespace Modules\Salon\Models;

class Resource extends SalonModel
{
    protected $table = 'salon_resources';

    protected $casts = [
        'equipment' => 'array',
    ];

    public function appointments() { return $this->hasMany(Appointment::class, 'salon_resource_id'); }
}
