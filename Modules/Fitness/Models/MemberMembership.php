<?php

namespace Modules\Fitness\Models;

use App\Models\Invoice;
use App\Models\Payment;

class MemberMembership extends FitnessModel
{
    public const STATUSES = ['Active', 'Expired', 'Suspended', 'Frozen', 'Cancelled', 'Pending'];

    protected $table = 'fitness_member_memberships';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'fitness_member_id',
        'fitness_membership_plan_id',
        'invoice_id',
        'membership_number',
        'starts_at',
        'ends_at',
        'renewal_date',
        'auto_renew',
        'status',
        'session_credits_remaining',
        'guest_passes_remaining',
        'price_charged',
        'joining_fee_charged',
        'balance',
        'last_renewed_at',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'renewal_date' => 'date',
        'auto_renew' => 'boolean',
        'price_charged' => 'decimal:2',
        'joining_fee_charged' => 'decimal:2',
        'balance' => 'decimal:2',
        'last_renewed_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'fitness_member_id');
    }

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'fitness_membership_plan_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function freezes()
    {
        return $this->hasMany(MembershipFreeze::class, 'fitness_member_membership_id');
    }

    public function events()
    {
        return $this->hasMany(MembershipEvent::class, 'fitness_member_membership_id');
    }
}
