<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'plan_id',
        'invoice_number',
        'billing_email',
        'customer_name',
        'status',
        'currency',
        'subtotal',
        'total',
        'due_at',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
    public function payments() { return $this->hasMany(SubscriptionPayment::class); }
    public function emailLogs() { return $this->morphMany(EmailLog::class, 'emailable'); }

    public function markPaid(?SubscriptionPayment $payment = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($payment) {
            $metadata['paid_payment_id'] = $payment->id;
            $metadata['paid_provider'] = $payment->provider;
        }

        $this->forceFill([
            'status' => 'paid',
            'paid_at' => $this->paid_at ?: now(),
            'metadata' => $metadata,
        ])->save();
    }
}
