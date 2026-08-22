<?php

namespace Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationGuest extends HospitalityModel
{
    protected $table = 'hospitality_reservation_guests';

    protected $fillable = ['tenant_id', 'business_id', 'reservation_id', 'guest_profile_id', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guestProfile(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class);
    }
}
