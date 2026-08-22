<?php

namespace Modules\Salon\Models;

use App\Models\User;

class StaffProfile extends SalonModel
{
    protected $table = 'salon_staff_profiles';

    protected $casts = [
        'specialties' => 'array',
        'commission_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function appointments() { return $this->hasMany(Appointment::class, 'salon_staff_profile_id'); }
    public function schedules() { return $this->hasMany(StaffSchedule::class, 'salon_staff_profile_id'); }
    public function commissions() { return $this->hasMany(Commission::class, 'salon_staff_profile_id'); }
}
