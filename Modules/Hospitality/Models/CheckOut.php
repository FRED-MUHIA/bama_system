<?php

namespace Modules\Hospitality\Models;

use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckOut extends HospitalityModel
{
    protected $table = 'hospitality_check_outs';

    protected $fillable = ['tenant_id', 'business_id', 'reservation_id', 'room_id', 'guest_profile_id', 'invoice_id', 'receipt_id', 'restaurant_charges', 'event_charges', 'other_charges', 'final_amount', 'checked_out_at', 'created_by', 'notes'];

    protected $casts = ['checked_out_at' => 'datetime', 'restaurant_charges' => 'decimal:2', 'event_charges' => 'decimal:2', 'other_charges' => 'decimal:2', 'final_amount' => 'decimal:2'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
