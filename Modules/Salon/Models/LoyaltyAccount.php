<?php

namespace Modules\Salon\Models;

class LoyaltyAccount extends SalonModel
{
    protected $table = 'salon_loyalty_accounts';

    protected $casts = [
        'last_activity_at' => 'datetime',
        'ledger' => 'array',
    ];

    public function profile() { return $this->belongsTo(ClientProfile::class, 'salon_client_profile_id'); }
}
