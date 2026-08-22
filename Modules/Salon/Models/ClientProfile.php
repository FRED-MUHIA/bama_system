<?php

namespace Modules\Salon\Models;

use App\Models\Client;

class ClientProfile extends SalonModel
{
    protected $table = 'salon_client_profiles';

    protected $casts = [
        'preferences' => 'array',
        'allergies' => 'array',
        'skin_hair_profile' => 'array',
        'date_of_birth' => 'date',
        'last_visit_at' => 'datetime',
        'lifetime_spend' => 'decimal:2',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function appointments() { return $this->hasMany(Appointment::class, 'salon_client_profile_id'); }
    public function loyaltyAccount() { return $this->hasOne(LoyaltyAccount::class, 'salon_client_profile_id'); }
    public function memberships() { return $this->hasMany(Membership::class, 'salon_client_profile_id'); }
}
