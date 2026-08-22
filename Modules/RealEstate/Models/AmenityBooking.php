<?php

namespace Modules\RealEstate\Models;

use App\Models\Invoice;

class AmenityBooking extends RealEstateModel
{
    protected $table = 'real_estate_amenity_bookings';
    protected $casts = ['booking_date' => 'date', 'charge_amount' => 'decimal:2'];

    public function amenity() { return $this->belongsTo(Amenity::class, 'real_estate_amenity_id'); }
    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
