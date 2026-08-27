<?php

namespace App\Models;

use App\Casts\TolerantEncryptedString;
use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\AuditsAccountingChanges;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use BelongsToBusiness, AuditsAccountingChanges;

    public const STATUSES = ['Lead', 'Quoted', 'Approved', 'Procurement', 'Installation', 'Testing', 'Handover', 'Closed'];

    protected $fillable = ['business_id', 'client_id', 'site_id', 'cost_center_id', 'contact_id', 'project_name', 'status', 'scope', 'notes'];

    protected function casts(): array
    {
        return [
            'scope' => TolerantEncryptedString::class,
            'notes' => TolerantEncryptedString::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Project $project) {
            if (\Illuminate\Support\Facades\Schema::hasTable('cost_centers')) {
                app(\App\Services\CostAccountingService::class)->ensureProjectCostCenter($project);
            }
        });
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function costs() { return $this->hasMany(ProjectCost::class); }
    public function expenses() { return $this->hasMany(ProjectExpense::class); }
    public function supplierQuotes() { return $this->hasMany(SupplierQuote::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
    public function supplierInvoices() { return $this->hasMany(SupplierInvoice::class); }
    public function warranties() { return $this->hasMany(Warranty::class); }
    public function documents() { return $this->hasMany(ProjectDocument::class); }
    public function handovers() { return $this->hasMany(HandoverRecord::class); }
    public function receiptAllocations() { return $this->hasMany(ReceiptAllocation::class); }
    public function letters() { return $this->hasMany(Letter::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }

    public function expectedCost(): float { return (float) $this->costs->sum('expected_amount'); }
    public function actualCost(): float { return (float) $this->costs->sum('actual_amount') + (float) $this->expenses->sum('amount') + (float) $this->supplierInvoices->sum('total'); }
    public function revenue(): float { return (float) $this->invoices->whereNull('parent_invoice_id')->sum('total'); }
    public function collected(): float { return (float) $this->invoices->sum('amount_paid') + (float) $this->receiptAllocations->sum('amount'); }
}
