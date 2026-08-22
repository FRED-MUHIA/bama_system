<?php

namespace Modules\RealEstate\Models;

use App\Models\User;

class ServiceRequest extends RealEstateModel
{
    protected $table = 'real_estate_service_requests';
    protected $casts = ['resolved_at' => 'datetime'];

    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
}
