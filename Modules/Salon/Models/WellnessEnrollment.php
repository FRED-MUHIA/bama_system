<?php

namespace Modules\Salon\Models;

class WellnessEnrollment extends SalonModel
{
    protected $table = 'salon_wellness_enrollments';

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'progress' => 'array',
    ];

    public function program() { return $this->belongsTo(WellnessProgram::class, 'salon_wellness_program_id'); }
    public function profile() { return $this->belongsTo(ClientProfile::class, 'salon_client_profile_id'); }
}
