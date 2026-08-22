<?php

namespace Modules\Fitness\Models;

use App\Models\User;

class MembershipEvent extends FitnessModel
{
    protected $table = 'fitness_membership_events';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'fitness_member_membership_id',
        'user_id',
        'event',
        'notes',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function membership()
    {
        return $this->belongsTo(MemberMembership::class, 'fitness_member_membership_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
