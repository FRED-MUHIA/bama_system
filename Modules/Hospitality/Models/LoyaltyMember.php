<?php

namespace Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyMember extends HospitalityModel
{
    protected $table = 'hospitality_loyalty_members';

    protected $fillable = ['tenant_id', 'business_id', 'guest_profile_id', 'membership_number', 'level', 'points_balance', 'lifetime_points', 'joined_at', 'last_redemption_at', 'rewards'];

    protected $casts = ['joined_at' => 'datetime', 'last_redemption_at' => 'datetime', 'rewards' => 'array'];

    public const LEVELS = ['Bronze', 'Silver', 'Gold', 'Platinum'];

    public function guestProfile(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class);
    }
}
