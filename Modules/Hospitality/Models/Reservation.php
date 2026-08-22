<?php

namespace Modules\Hospitality\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends HospitalityModel
{
    protected $table = 'hospitality_reservations';

    protected $fillable = ['tenant_id', 'business_id', 'guest_profile_id', 'client_id', 'room_id', 'room_type_id', 'reservation_number', 'arrival_date', 'departure_date', 'adults', 'children', 'special_requests', 'booking_source', 'status', 'deposit_amount', 'total_amount', 'checked_in_at', 'checked_out_at', 'cancelled_at'];

    protected $casts = ['arrival_date' => 'date', 'departure_date' => 'date', 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime', 'cancelled_at' => 'datetime', 'deposit_amount' => 'decimal:2', 'total_amount' => 'decimal:2'];

    public const BOOKING_SOURCES = ['Website', 'Walk In', 'Phone', 'Agent', 'OTA'];
    public const STATUSES = ['Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled', 'No Show'];

    public function guestProfile(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }
}
