<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'payload_json',
        'signature_valid',
        'processed',
        'processing_error',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
