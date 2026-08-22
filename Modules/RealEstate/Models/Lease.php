<?php

namespace Modules\RealEstate\Models;

use App\Models\DocumentTemplate;

class Lease extends RealEstateModel
{
    protected $table = 'real_estate_leases';
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'next_bill_date' => 'date', 'rent_amount' => 'decimal:2', 'deposit_amount' => 'decimal:2', 'auto_billing' => 'boolean'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function documentTemplate() { return $this->belongsTo(DocumentTemplate::class); }
    public function charges() { return $this->hasMany(RentalCharge::class, 'real_estate_lease_id'); }
    public function utilityBills() { return $this->hasMany(UtilityBill::class, 'real_estate_lease_id'); }
    public function ledgerEntries() { return $this->hasMany(TenantLedger::class, 'real_estate_lease_id'); }
}
