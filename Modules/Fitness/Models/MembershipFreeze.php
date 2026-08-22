<?php

namespace Modules\Fitness\Models;

use App\Models\User;

class MembershipFreeze extends FitnessModel
{
    protected $table = 'fitness_membership_freezes';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'fitness_member_membership_id',
        'starts_at',
        'ends_at',
        'reason',
        'status',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function membership()
    {
        return $this->belongsTo(MemberMembership::class, 'fitness_member_membership_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
