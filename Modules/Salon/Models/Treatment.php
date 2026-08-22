<?php

namespace Modules\Salon\Models;

class Treatment extends SalonModel
{
    protected $table = 'salon_treatments';

    protected $casts = [
        'performed_on' => 'date',
        'products_used' => 'array',
        'aftercare' => 'array',
    ];

    public function profile() { return $this->belongsTo(ClientProfile::class, 'salon_client_profile_id'); }
    public function appointment() { return $this->belongsTo(Appointment::class, 'salon_appointment_id'); }
    public function service() { return $this->belongsTo(Service::class, 'salon_service_id'); }
    public function staff() { return $this->belongsTo(StaffProfile::class, 'salon_staff_profile_id'); }
}
