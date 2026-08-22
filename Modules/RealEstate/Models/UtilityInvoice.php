<?php

namespace Modules\RealEstate\Models;

use App\Models\Invoice;

class UtilityInvoice extends RealEstateModel
{
    protected $table = 'real_estate_utility_invoices';
    protected $casts = ['total' => 'decimal:2'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function tenant() { return $this->belongsTo(Tenant::class, 'real_estate_tenant_id'); }
    public function statement() { return $this->belongsTo(TenantStatement::class, 'real_estate_tenant_statement_id'); }
}
