<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionPaymentService
{
    public function __construct(private readonly PaymentAuditService $audit) {}

    public function transition(SubscriptionPayment $payment, PaymentStatus $next, array $attributes = [], bool $verifiedCorrection = false): SubscriptionPayment
    {
        $current = $this->status($payment);

        if (! $verifiedCorrection && $current && $current !== $next && ! $current->canTransitionTo($next)) {
            throw new RuntimeException("Invalid payment status transition from {$current->value} to {$next->value}.");
        }

        $timestamps = match ($next) {
            PaymentStatus::Successful => ['completed_at' => $attributes['completed_at'] ?? now(), 'paid_at' => $attributes['paid_at'] ?? now()],
            PaymentStatus::Failed => ['failed_at' => $attributes['failed_at'] ?? now()],
            PaymentStatus::Cancelled => ['cancelled_at' => $attributes['cancelled_at'] ?? now()],
            PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded => ['refunded_at' => $attributes['refunded_at'] ?? now()],
            default => [],
        };

        $payment->forceFill(array_merge($attributes, $timestamps, ['status' => $next->value]))->save();
        $this->audit->record($payment, 'payment_'.$next->value, ['attributes' => $attributes]);

        return $payment->refresh();
    }

    public function activateAfterVerifiedPayment(SubscriptionPayment $payment, array $verification = []): SubscriptionInvoice
    {
        $invoice = DB::transaction(function () use ($payment, $verification) {
            $lockedPayment = SubscriptionPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $invoice = $lockedPayment->invoice()->lockForUpdate()->firstOrFail();
            $subscription = $invoice->subscription()->lockForUpdate()->first();
            $tenant = $invoice->tenant()->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== PaymentStatus::Successful->value) {
                throw new RuntimeException('Subscription activation requires a verified successful payment.');
            }

            if ($lockedPayment->processed_at) {
                return $invoice->refresh();
            }

            if ((float) $lockedPayment->amount !== (float) $invoice->total) {
                throw new RuntimeException('Verified payment amount does not match the subscription invoice.');
            }

            if (strtoupper($lockedPayment->currency) !== strtoupper($invoice->currency)) {
                throw new RuntimeException('Verified payment currency does not match the subscription invoice.');
            }

            if ((int) $lockedPayment->tenant_id !== (int) $invoice->tenant_id) {
                throw new RuntimeException('Verified payment tenant does not match the subscription invoice.');
            }

            if ($invoice->plan_id && $subscription?->plan_id && (int) $invoice->plan_id !== (int) $subscription->plan_id) {
                $metadata = $invoice->metadata ?? [];
                $metadata['plan_change_payment'] = true;
                $invoice->forceFill(['metadata' => $metadata])->save();
            }

            if ($lockedPayment->provider_payment_id) {
                $duplicate = SubscriptionPayment::query()
                    ->where('provider', $lockedPayment->provider)
                    ->where('provider_payment_id', $lockedPayment->provider_payment_id)
                    ->where('status', PaymentStatus::Successful->value)
                    ->whereKeyNot($lockedPayment->id)
                    ->exists();

                if ($duplicate) {
                    throw new RuntimeException('This gateway transaction has already been used for another payment.');
                }
            }

            $invoice->markPaid($lockedPayment);

            if ($subscription) {
                $base = now();
                if ($subscription->renews_at && $subscription->renews_at->isFuture()) {
                    $base = $subscription->renews_at;
                }

                $subscription->forceFill([
                    'plan_id' => $invoice->plan_id ?: $subscription->plan_id,
                    'status' => 'active',
                    'starts_at' => $subscription->starts_at ?: now(),
                    'renews_at' => $base->copy()->addMonthNoOverflow(),
                    'grace_ends_at' => null,
                    'ends_at' => null,
                    'locked_at' => null,
                    'last_renewal_notice_sent_at' => null,
                    'last_grace_notice_sent_at' => null,
                ])->save();
            }

            $tenant->forceFill(['status' => 'active'])->save();
            $lockedPayment->forceFill(['processed_at' => now()])->save();

            $this->audit->record($lockedPayment, 'subscription_activated', $verification);

            return $invoice->refresh();
        });

        app(SubscriptionBillingService::class)->sendInvoice($invoice, 'paid');

        return $invoice;
    }

    private function status(SubscriptionPayment $payment): ?PaymentStatus
    {
        return PaymentStatus::tryFrom($payment->status === 'paid' ? PaymentStatus::Successful->value : $payment->status);
    }
}
