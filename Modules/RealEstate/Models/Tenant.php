<?php

namespace Modules\RealEstate\Models;

use App\Models\Client;
use App\Models\EmailLog;

class Tenant extends RealEstateModel
{
    protected $table = 'real_estate_tenants';
    protected $casts = [
        'notice_date' => 'date',
        'termination_date' => 'date',
        'final_inspection_date' => 'date',
        'utility_reconciled_at' => 'datetime',
        'final_billed_at' => 'datetime',
        'deposit_settled_at' => 'datetime',
        'move_out_date' => 'date',
        'archived_at' => 'datetime',
        'restored_at' => 'datetime',
        'billing_alert_enabled' => 'boolean',
        'last_billing_alert_sent_at' => 'datetime',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function leases() { return $this->hasMany(Lease::class, 'real_estate_tenant_id'); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'real_estate_tenant_id'); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class, 'real_estate_tenant_id'); }
    public function utilityMeters() { return $this->hasMany(UtilityMeter::class, 'real_estate_tenant_id'); }
    public function utilityBills() { return $this->hasMany(UtilityBill::class, 'real_estate_tenant_id'); }
    public function amenityBookings() { return $this->hasMany(AmenityBooking::class, 'real_estate_tenant_id'); }
    public function ledgerEntries() { return $this->hasMany(TenantLedger::class, 'real_estate_tenant_id'); }
    public function statements() { return $this->hasMany(TenantStatement::class, 'real_estate_tenant_id'); }
    public function documents() { return $this->morphMany(RealEstateDocument::class, 'documentable'); }
    public function utilityReadings() { return $this->hasMany(MeterReading::class, 'real_estate_tenant_id'); }
    public function emailLogs() { return $this->morphMany(EmailLog::class, 'emailable'); }

    public function scopeCurrent($query)
    {
        return $query->whereNotIn('status', ['Moved Out', 'Archived', 'Blacklisted']);
    }

    public function scopeArchivedLifecycle($query)
    {
        return $query->whereIn('status', ['Moved Out', 'Archived', 'Blacklisted']);
    }
}
