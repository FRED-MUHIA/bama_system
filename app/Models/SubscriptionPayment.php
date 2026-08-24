<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_invoice_id',
        'tenant_id',
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
        'callback_payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'callback_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice() { return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id'); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
}
