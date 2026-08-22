<?php

namespace Modules\RealEstate\Models;

use App\Models\Supplier;
use App\Models\User;

class MaintenanceRequest extends RealEstateModel
{
    protected $table = 'real_estate_maintenance_requests';
    protected $casts = ['scheduled_date' => 'date', 'resolved_at' => 'datetime', 'estimated_cost' => 'decimal:2', 'actual_cost' => 'decimal:2'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
