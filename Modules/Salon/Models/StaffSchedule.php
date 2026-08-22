<?php

namespace Modules\Salon\Models;

class StaffSchedule extends SalonModel
{
    protected $table = 'salon_staff_schedules';

    protected $casts = [
        'work_date' => 'date',
    ];

    public function staff() { return $this->belongsTo(StaffProfile::class, 'salon_staff_profile_id'); }
}
