<?php

namespace Modules\Salon\Models;

use App\Models\Payment;

class Commission extends SalonModel
{
    protected $table = 'salon_commissions';

    protected $casts = [
        'commission_date' => 'date',
        'base_amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function staff() { return $this->belongsTo(StaffProfile::class, 'salon_staff_profile_id'); }
    public function appointment() { return $this->belongsTo(Appointment::class, 'salon_appointment_id'); }
    public function payment() { return $this->belongsTo(Payment::class); }
}
