<?php

namespace Modules\Hospitality\Models;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBooking extends HospitalityModel
{
    protected $table = 'hospitality_event_bookings';

    protected $fillable = ['tenant_id', 'business_id', 'client_id', 'guest_profile_id', 'invoice_id', 'booking_number', 'venue_name', 'event_type', 'starts_at', 'ends_at', 'attendees', 'package_name', 'catering', 'equipment', 'status', 'total_amount', 'notes'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'catering' => 'array', 'equipment' => 'array', 'total_amount' => 'decimal:2'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function guestProfile(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
