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
        static::created(function (SupplierPayment $payment) {
            app(\App\Services\FinanceService::class)->postSupplierPayment($payment->load('supplierInvoice'));
            app(\App\Services\FinanceRecordSyncService::class)->syncSupplierInvoiceId($payment->supplier_invoice_id, $payment->business_id);
        });

        static::updated(function (SupplierPayment $payment) {
            app(\App\Services\FinanceService::class)->postSupplierPayment($payment->load('supplierInvoice'));

            $sync = app(\App\Services\FinanceRecordSyncService::class);
            $oldInvoiceId = $payment->getOriginal('supplier_invoice_id');
            if ($oldInvoiceId && (int) $oldInvoiceId !== (int) $payment->supplier_invoice_id) {
                $sync->syncSupplierInvoiceId((int) $oldInvoiceId, $payment->getOriginal('business_id'));
            }
            $sync->syncSupplierInvoiceId($payment->supplier_invoice_id, $payment->business_id);
        });

        static::deleted(function (SupplierPayment $payment) {
            app(\App\Services\FinanceRecordSyncService::class)->syncSupplierInvoiceId($payment->supplier_invoice_id, $payment->business_id);
        });
    }

    protected $fillable = ['business_id', 'supplier_invoice_id', 'department_id', 'cost_center_id', 'amount', 'payment_date', 'reference', 'notes'];
    protected $casts = ['payment_date' => 'date'];

    public function supplierInvoice() { return $this->belongsTo(SupplierInvoice::class); }
}
