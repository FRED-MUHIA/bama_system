<?php

namespace Modules\Salon\Models;

class Consultation extends SalonModel
{
    protected $table = 'salon_consultations';

    protected $casts = [
        'observations' => 'array',
        'recommendations' => 'array',
        'contraindications' => 'array',
        'follow_up_date' => 'date',
    ];

    public function profile() { return $this->belongsTo(ClientProfile::class, 'salon_client_profile_id'); }
    public function appointment() { return $this->belongsTo(Appointment::class, 'salon_appointment_id'); }
    public function staff() { return $this->belongsTo(StaffProfile::class, 'salon_staff_profile_id'); }
}
