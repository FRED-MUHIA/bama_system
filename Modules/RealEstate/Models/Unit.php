<?php

namespace Modules\RealEstate\Models;

class Unit extends RealEstateModel
{
    protected $table = 'real_estate_units';
    protected $casts = ['rent_amount' => 'decimal:2', 'sale_price' => 'decimal:2', 'square_footage' => 'decimal:2'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function leases() { return $this->hasMany(Lease::class, 'real_estate_unit_id'); }
    public function sales() { return $this->hasMany(Sale::class, 'real_estate_unit_id'); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'real_estate_unit_id'); }
    public function utilityMeters() { return $this->hasMany(UtilityMeter::class, 'real_estate_unit_id'); }
    public function utilityBills() { return $this->hasMany(UtilityBill::class, 'real_estate_unit_id'); }
    public function amenityBookings() { return $this->hasMany(AmenityBooking::class, 'real_estate_unit_id'); }
}
