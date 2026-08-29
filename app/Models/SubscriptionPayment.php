<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_invoice_id',
        'tenant_id',
        'merchant_reference',
        'provider',
        'status',
        'amount',
        'currency',
        'checkout_request_id',
        'merchant_request_id',
        'provider_order_id',
        'provider_payment_id',
        'provider_receipt',
        'phone',
        'payment_url',
        'request_payload',
        'response_payload',
        'callback_payload',
        'failure_code',
        'failure_message',
        'paid_at',
        'initiated_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'refunded_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'callback_payload' => 'array',
            'paid_at' => 'datetime',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, ['successful', 'paid'], true);
    }

    public function maskedPhone(): ?string
    {
        if (! $this->phone || strlen($this->phone) < 7) {
            return $this->phone;
        }

        return substr($this->phone, 0, 4).'****'.substr($this->phone, -3);
    }
}
