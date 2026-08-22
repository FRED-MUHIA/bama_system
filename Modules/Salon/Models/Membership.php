<?php

namespace Modules\Salon\Models;

use App\Models\Invoice;

class Membership extends SalonModel
{
    protected $table = 'salon_memberships';

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'balance' => 'decimal:2',
    ];

    public function profile() { return $this->belongsTo(ClientProfile::class, 'salon_client_profile_id'); }
    public function plan() { return $this->belongsTo(MembershipPlan::class, 'salon_membership_plan_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
