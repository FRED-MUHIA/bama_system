<?php

namespace Modules\RealEstate\Models;

use App\Models\Invoice;

class RentalCharge extends RealEstateModel
{
    protected $table = 'real_estate_rental_charges';
    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date', 'amount' => 'decimal:2', 'penalty_amount' => 'decimal:2'];

    public function lease() { return $this->belongsTo(Lease::class, 'real_estate_lease_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
