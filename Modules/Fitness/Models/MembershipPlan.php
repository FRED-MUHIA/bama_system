<?php

namespace Modules\Fitness\Models;

class MembershipPlan extends FitnessModel
{
    public const TYPES = ['Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'Corporate', 'Student', 'Premium VIP'];
    public const STATUSES = ['Active', 'Inactive', 'Archived'];

    protected $table = 'fitness_membership_plans';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'name',
        'code',
        'plan_type',
        'description',
        'currency',
        'price',
        'joining_fee',
        'renewal_fee',
        'duration_days',
        'session_credits',
        'freeze_allowed',
        'guest_passes',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'joining_fee' => 'decimal:2',
        'renewal_fee' => 'decimal:2',
        'freeze_allowed' => 'boolean',
    ];

    public function memberships()
    {
        return $this->hasMany(MemberMembership::class, 'fitness_membership_plan_id');
    }
}
