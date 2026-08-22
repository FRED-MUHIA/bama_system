<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\AuditsAccountingChanges;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToBusiness, AuditsAccountingChanges;

    protected static function booted(): void
    {
        static::created(fn (Payment $payment) => app(\App\Services\FinanceService::class)->postPayment($payment->load('invoice')));
    }

    protected $fillable = ['business_id', 'invoice_id', 'payable_type', 'payable_id', 'department_id', 'cost_center_id', 'payment_method_id', 'amount', 'payment_date', 'reference', 'notes'];
    protected $casts = ['payment_date' => 'date'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function payable() { return $this->morphTo(); }
    public function paymentMethod() { return $this->belongsTo(PaymentMethod::class); }
    public function receipt() { return $this->hasOne(Receipt::class); }
}
