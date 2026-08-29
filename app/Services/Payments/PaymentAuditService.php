<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymentAuditService
{
    public function record(?SubscriptionPayment $payment, string $event, array $context = []): void
    {
        $safeContext = $this->sanitize($context);

        if ($payment && Schema::hasTable('payment_audit_logs')) {
            DB::table('payment_audit_logs')->insert([
                'subscription_payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'event' => $event,
                'context_json' => json_encode($safeContext),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::channel(config('logging.channels.payments') ? 'payments' : config('logging.default'))->info('Payment audit: '.$event, [
            'payment_id' => $payment?->id,
            'tenant_id' => $payment?->tenant_id,
            'provider' => $payment?->provider,
            'context' => $safeContext,
        ]);
    }

    public function sanitize(array $payload): array
    {
        $blocked = ['authorization', 'password', 'passkey', 'secret', 'client_secret', 'access_token', 'consumer_secret', 'cvv', 'card_number'];

        $sanitize = function ($value, $key = null) use (&$sanitize, $blocked) {
            if (is_string($key)) {
                $lower = strtolower($key);
                foreach ($blocked as $needle) {
                    if (str_contains($lower, $needle)) {
                        return '[redacted]';
                    }
                }
            }

            if (is_array($value)) {
                return collect($value)->map(fn ($item, $itemKey) => $sanitize($item, $itemKey))->all();
            }

            return $value;
        };

        return $sanitize($payload);
    }
}
