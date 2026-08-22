<?php

namespace Modules\Fitness\Models;

use App\Models\Client;
use App\Models\User;

class Member extends FitnessModel
{
    public const STATUSES = ['Active', 'Expired', 'Suspended', 'Frozen', 'Cancelled', 'Pending'];

    protected $table = 'fitness_members';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'client_id',
        'assigned_trainer_id',
        'member_number',
        'photo_path',
        'gender',
        'date_of_birth',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'occupation',
        'join_date',
        'status',
        'qr_code',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
        'metadata' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedTrainer()
    {
        return $this->belongsTo(User::class, 'assigned_trainer_id');
    }

    public function memberships()
    {
        return $this->hasMany(MemberMembership::class, 'fitness_member_id');
    }

    public function activeMembership()
    {
        return $this->hasOne(MemberMembership::class, 'fitness_member_id')->where('status', 'Active')->latestOfMany();
    }
}
