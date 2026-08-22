<?php

namespace Modules\Salon\Models;

class WellnessProgram extends SalonModel
{
    protected $table = 'salon_wellness_programs';

    protected $casts = [
        'price' => 'decimal:2',
        'milestones' => 'array',
        'is_active' => 'boolean',
    ];

    public function enrollments() { return $this->hasMany(WellnessEnrollment::class, 'salon_wellness_program_id'); }
}
