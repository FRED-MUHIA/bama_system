<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\AuditsAccountingChanges;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    use BelongsToBusiness, AuditsAccountingChanges;

    protected static function booted(): void
    {
        static::created(fn (SupplierInvoice $invoice) => app(\App\Services\FinanceService::class)->postSupplierInvoice($invoice));
    }

    protected $fillable = ['business_id', 'supplier_id', 'project_id', 'department_id', 'cost_center_id', 'purchase_order_id', 'invoice_number', 'invoice_date', 'due_date', 'total', 'amount_paid', 'status', 'notes'];
    protected $casts = ['invoice_date' => 'date', 'due_date' => 'date'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function payments() { return $this->hasMany(SupplierPayment::class); }
}
