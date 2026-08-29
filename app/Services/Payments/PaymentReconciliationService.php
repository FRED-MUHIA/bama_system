<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\SubscriptionPayment;
use App\Services\Billing\PaymentGatewayService;

class PaymentReconciliationService
{
    public function __construct(private readonly PaymentGatewayService $gateway) {}

    public function reconcile(?string $gateway = null, ?string $status = null, int $limit = 100): array
    {
        $query = SubscriptionPayment::query()
            ->whereIn('status', [
                $status ?: PaymentStatus::Pending->value,
                $status ?: PaymentStatus::Processing->value,
                $status ?: PaymentStatus::RequiresAction->value,
            ])
            ->latest()
            ->limit($limit);

        if ($gateway) {
            $query->where('provider', $gateway);
        }

        $stats = ['checked' => 0, 'successful' => 0, 'failed' => 0, 'errors' => 0];

        foreach ($query->get() as $payment) {
            $stats['checked']++;

            try {
                $updated = match ($payment->provider) {
                    'mpesa' => $this->gateway->queryMpesaStatus($payment),
                    'paypal' => $this->gateway->queryPayPalOrder($payment),
                    default => $payment,
                };

                if ($updated->status === PaymentStatus::Successful->value) {
                    $stats['successful']++;
                } elseif ($updated->status === PaymentStatus::Failed->value) {
                    $stats['failed']++;
                }
            } catch (\Throwable) {
                $stats['errors']++;
            }
        }

        return $stats;
    }
}
