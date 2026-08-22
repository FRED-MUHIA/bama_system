<?php

namespace Modules\RealEstate\Models;

use App\Models\Branch;
use App\Models\User;

class Property extends RealEstateModel
{
    protected $table = 'real_estate_properties';
    protected $casts = ['acquisition_date' => 'date', 'acquisition_cost' => 'decimal:2', 'market_value' => 'decimal:2', 'images' => 'array'];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function manager() { return $this->belongsTo(User::class, 'property_manager_id'); }
    public function units() { return $this->hasMany(Unit::class, 'real_estate_property_id'); }
    public function listings() { return $this->hasMany(Listing::class, 'real_estate_property_id'); }
    public function leases() { return $this->hasMany(Lease::class, 'real_estate_property_id'); }
    public function sales() { return $this->hasMany(Sale::class, 'real_estate_property_id'); }
    public function inspections() { return $this->hasMany(Inspection::class, 'real_estate_property_id'); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'real_estate_property_id'); }
    public function valuations() { return $this->hasMany(Valuation::class, 'real_estate_property_id'); }
    public function landParcels() { return $this->hasMany(LandParcel::class, 'real_estate_property_id'); }
    public function developmentProjects() { return $this->hasMany(DevelopmentProject::class, 'real_estate_property_id'); }
    public function utilityMeters() { return $this->hasMany(UtilityMeter::class, 'real_estate_property_id'); }
    public function utilityBills() { return $this->hasMany(UtilityBill::class, 'real_estate_property_id'); }
    public function amenities() { return $this->hasMany(Amenity::class, 'real_estate_property_id'); }
}
