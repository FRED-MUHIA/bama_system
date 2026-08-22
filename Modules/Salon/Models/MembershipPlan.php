<?php

namespace Modules\Salon\Models;

class MembershipPlan extends SalonModel
{
    protected $table = 'salon_membership_plans';

    protected $casts = [
        'price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    public function memberships() { return $this->hasMany(Membership::class, 'salon_membership_plan_id'); }
}
