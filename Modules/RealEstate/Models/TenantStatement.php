<?php

namespace Modules\RealEstate\Models;

class TenantStatement extends RealEstateModel
{
    protected $table = 'real_estate_tenant_statements';
    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'previous_balance' => 'decimal:2', 'current_charges' => 'decimal:2', 'payments_made' => 'decimal:2', 'outstanding_balance' => 'decimal:2', 'summary' => 'array'];

    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function lease() { return $this->belongsTo(Lease::class, 'real_estate_lease_id'); }
}
