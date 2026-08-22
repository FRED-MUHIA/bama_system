<?php

namespace Modules\Salon\Models;

class AppointmentService extends SalonModel
{
    protected $table = 'salon_appointment_services';

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function appointment() { return $this->belongsTo(Appointment::class, 'salon_appointment_id'); }
    public function service() { return $this->belongsTo(Service::class, 'salon_service_id'); }
    public function staff() { return $this->belongsTo(StaffProfile::class, 'salon_staff_profile_id'); }
}
