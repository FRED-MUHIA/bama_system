<?php

namespace Modules\RealEstate\Models;

use App\Models\Invoice;
use App\Models\Payment;

class TenantLedger extends RealEstateModel
{
    protected $table = 'real_estate_tenant_ledgers';
    protected $casts = ['entry_date' => 'date', 'debit' => 'decimal:2', 'credit' => 'decimal:2', 'running_balance' => 'decimal:2'];

    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function lease() { return $this->belongsTo(Lease::class, 'real_estate_lease_id'); }
    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function ledgerable() { return $this->morphTo(); }
}
