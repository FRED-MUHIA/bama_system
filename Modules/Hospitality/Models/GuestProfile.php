<?php

namespace Modules\Hospitality\Models;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestProfile extends HospitalityModel
{
    protected $table = 'hospitality_guest_profiles';

    protected $fillable = ['tenant_id', 'business_id', 'client_id', 'contact_id', 'full_name', 'phone', 'email', 'nationality', 'passport_number', 'id_number', 'address', 'preferences', 'vip_status', 'blacklist_flag', 'loyalty_level'];

    protected $casts = ['preferences' => 'array', 'vip_status' => 'boolean', 'blacklist_flag' => 'boolean'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function loyaltyMember()
    {
        return $this->hasOne(LoyaltyMember::class);
    }
}
