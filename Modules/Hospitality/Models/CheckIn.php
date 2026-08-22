<?php

namespace Modules\Hospitality\Models;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends HospitalityModel
{
    protected $table = 'hospitality_check_ins';

    protected $fillable = ['tenant_id', 'business_id', 'reservation_id', 'room_id', 'guest_profile_id', 'invoice_id', 'access_code', 'deposit_amount', 'checked_in_at', 'created_by', 'notes'];

    protected $casts = ['checked_in_at' => 'datetime', 'deposit_amount' => 'decimal:2'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
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
