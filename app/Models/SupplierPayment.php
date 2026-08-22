<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\AuditsAccountingChanges;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use BelongsToBusiness, AuditsAccountingChanges;

    protected static function booted(): void
    {
        static::created(fn (SupplierPayment $payment) => app(\App\Services\FinanceService::class)->postSupplierPayment($payment->load('supplierInvoice')));
    }

    protected $fillable = ['business_id', 'supplier_invoice_id', 'department_id', 'cost_center_id', 'amount', 'payment_date', 'reference', 'notes'];
    protected $casts = ['payment_date' => 'date'];

    public function supplierInvoice() { return $this->belongsTo(SupplierInvoice::class); }
}
