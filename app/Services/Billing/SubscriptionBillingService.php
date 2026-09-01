<?php

namespace App\Services\Billing;

use App\Models\CompanySetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SubscriptionBillingService
{
    public function currentInvoiceFor(Tenant $tenant, ?Plan $plan = null): SubscriptionInvoice
    {
        $subscription = $tenant->subscription?->loadMissing('plan');
        $plan ??= $subscription?->plan;

        abort_unless($subscription && $plan, 422, 'This profile does not have a billable package yet.');

        $invoice = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'sent', 'overdue'])
            ->latest()
            ->first();

        return $invoice ?: $this->createInvoice($subscription, $plan);
    }

    public function createInvoice(Subscription $subscription, ?Plan $plan = null, ?Carbon $dueAt = null): SubscriptionInvoice
    {
        $subscription->loadMissing('tenant', 'plan');
        $tenant = $subscription->tenant;
        $plan ??= $subscription->plan;
        $dueAt ??= $this->expiresAt($subscription) ?: now();
        $email = $this->billingEmails($tenant)[0] ?? null;
        $total = (float) $plan->monthly_price;

        return SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'invoice_number' => $this->invoiceNumber($tenant),
            'billing_email' => $email,
            'customer_name' => $tenant->name,
            'status' => 'pending',
            'currency' => strtoupper($plan->currency ?: 'KES'),
            'subtotal' => $total,
            'total' => $total,
            'due_at' => $dueAt,
            'metadata' => [
                'period' => 'monthly',
                'plan_name' => $plan->name,
                'grace_days' => 2,
            ],
        ]);
    }

    public function sendInvoice(SubscriptionInvoice $invoice, string $kind = 'invoice'): int
    {
        $invoice->loadMissing('tenant', 'plan');
        $emails = $this->billingEmails($invoice->tenant);

        if (! $emails && $invoice->billing_email) {
            $emails = [$invoice->billing_email];
        }

        if ($emails && $invoice->billing_email !== $emails[0]) {
            $invoice->forceFill(['billing_email' => $emails[0]])->save();
        }

        $sent = 0;
        foreach ($emails as $email) {
            $subject = $this->subject($invoice, $kind);
            $body = $this->emailBody($invoice, $kind);

            try {
                Mail::send(
                    ['html' => 'emails.bama-system', 'text' => 'emails.bama-text'],
                    [
                        'appName' => config('mail.brand.name', 'Bama'),
                        'subject' => $subject,
                        'headline' => $this->emailHeadline($kind),
                        'body' => $body,
                        'preheader' => str($body)->squish()->limit(140)->toString(),
                        'actionUrl' => $kind === 'paid' ? null : route('billing.index'),
                        'actionText' => 'Pay Bama invoice',
                        'footerNote' => $kind === 'paid' ? null : 'If the button does not work, copy and paste the payment link from this email into your browser.',
                    ],
                    fn ($mail) => $mail->to($email)->subject($subject)
                );
                $invoice->emailLogs()->create([
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'message' => $body,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $sent++;
            } catch (Throwable $e) {
                report($e);
                $invoice->emailLogs()->create([
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'message' => $body,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0 && $invoice->status === 'pending') {
            $invoice->update(['status' => 'sent']);
        }

        return $sent;
    }

    public function markPaid(SubscriptionPayment $payment): SubscriptionInvoice
    {
        $invoice = DB::transaction(function () use ($payment) {
            $payment->refresh();
            $invoice = $payment->invoice()->lockForUpdate()->firstOrFail();
            $subscription = $invoice->subscription()->lockForUpdate()->first();
            $tenant = $invoice->tenant()->lockForUpdate()->firstOrFail();

            $payment->forceFill([
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?: now(),
            ])->save();

            $invoice->markPaid($payment);

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

            return $invoice->refresh();
        });

        $this->sendInvoice($invoice, 'paid');

        return $invoice;
    }

    public function sweep(?Carbon $date = null): array
    {
        $date ??= now();
        $stats = ['renewal_notices' => 0, 'grace_notices' => 0, 'locked' => 0];

        if (! Schema::hasTable('subscriptions') || ! Schema::hasTable('subscription_invoices')) {
            return $stats;
        }

        Subscription::withoutGlobalScopes()
            ->with(['tenant.users', 'plan'])
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->chunkById(100, function ($subscriptions) use ($date, &$stats) {
                foreach ($subscriptions as $subscription) {
                    $expiresAt = $this->expiresAt($subscription);
                    if (! $expiresAt || ! $subscription->tenant || $subscription->tenant->status === 'cancelled') {
                        continue;
                    }

                    $graceEndsAt = $subscription->grace_ends_at ?: $expiresAt->copy()->addDays(2);

                    $daysUntilExpiry = (int) $date->copy()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);

                    if ($daysUntilExpiry >= 0 && $daysUntilExpiry <= 5 && ! $subscription->last_renewal_notice_sent_at) {
                        $invoice = $this->createOrFindCycleInvoice($subscription, $expiresAt);
                        $stats['renewal_notices'] += $this->sendInvoice($invoice, 'renewal');
                        $subscription->forceFill([
                            'last_renewal_notice_sent_at' => now(),
                            'grace_ends_at' => $graceEndsAt,
                        ])->save();
                    }

                    if ($expiresAt->isPast() && $graceEndsAt->isFuture() && ! $subscription->last_grace_notice_sent_at) {
                        $invoice = $this->createOrFindCycleInvoice($subscription, $expiresAt);
                        $stats['grace_notices'] += $this->sendInvoice($invoice, 'grace');
                        $subscription->forceFill([
                            'status' => 'past_due',
                            'grace_ends_at' => $graceEndsAt,
                            'last_grace_notice_sent_at' => now(),
                        ])->save();
                    }

                    if ($graceEndsAt->isPast() && ! $subscription->locked_at) {
                        $subscription->forceFill([
                            'status' => 'paused',
                            'grace_ends_at' => $graceEndsAt,
                            'locked_at' => now(),
                            'ends_at' => $subscription->ends_at ?: $expiresAt,
                        ])->save();
                        $subscription->tenant->forceFill(['status' => 'suspended'])->save();
                        $stats['locked']++;
                    }
                }
            });

        return $stats;
    }

    public function billingEmails(Tenant $tenant): array
    {
        $profileEmails = [];

        if (Schema::hasTable('company_settings')) {
            if (Schema::hasColumn('company_settings', 'tenant_id')) {
                $profileEmails = array_merge($profileEmails, CompanySetting::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->pluck('email')
                    ->filter()
                    ->all());
            }

            if (Schema::hasColumn('company_settings', 'business_id') && Schema::hasTable('businesses')) {
                $businessIds = $tenant->businesses()
                    ->withoutGlobalScopes()
                    ->pluck('id')
                    ->all();

                if ($businessIds) {
                    $profileEmails = array_merge($profileEmails, CompanySetting::withoutGlobalScopes()
                        ->whereIn('business_id', $businessIds)
                        ->pluck('email')
                        ->filter()
                        ->all());
                }
            }
        }

        $settingsEmails = collect([
            data_get($tenant->settings, 'billing_email'),
            data_get($tenant->settings, 'email'),
        ])->filter()->all();

        $profileEmails = $this->normalizeEmails(array_merge($profileEmails, $settingsEmails));

        if ($profileEmails) {
            return $profileEmails;
        }

        return $this->normalizeEmails($tenant->users()
            ->where('users.is_active', true)
            ->pluck('users.email')
            ->filter()
            ->all());
    }

    private function normalizeEmails(array $emails): array
    {
        return collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private function createOrFindCycleInvoice(Subscription $subscription, Carbon $dueAt): SubscriptionInvoice
    {
        $invoice = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->whereDate('due_at', $dueAt->toDateString())
            ->whereIn('status', ['pending', 'sent', 'overdue'])
            ->latest()
            ->first();

        return $invoice ?: $this->createInvoice($subscription, $subscription->plan, $dueAt);
    }

    private function expiresAt(Subscription $subscription): ?Carbon
    {
        return $subscription->status === 'trialing' && $subscription->trial_ends_at
            ? $subscription->trial_ends_at
            : ($subscription->renews_at ?: $subscription->trial_ends_at);
    }

    private function invoiceNumber(Tenant $tenant): string
    {
        do {
            $number = 'BAMA-'.now()->format('Ymd').'-'.$tenant->id.'-'.Str::upper(Str::random(5));
        } while (SubscriptionInvoice::where('invoice_number', $number)->exists());

        return $number;
    }

    private function subject(SubscriptionInvoice $invoice, string $kind): string
    {
        return match ($kind) {
            'renewal' => 'Bama subscription renewal invoice '.$invoice->invoice_number,
            'grace' => 'Bama subscription grace period reminder '.$invoice->invoice_number,
            'paid' => 'Bama subscription payment received '.$invoice->invoice_number,
            default => 'Bama subscription invoice '.$invoice->invoice_number,
        };
    }

    private function emailHeadline(string $kind): string
    {
        return match ($kind) {
            'renewal' => 'Your Bama renewal invoice is ready.',
            'grace' => 'Your Bama workspace is in grace period.',
            'paid' => 'Your Bama payment has been received.',
            default => 'Your Bama invoice is ready.',
        };
    }

    private function emailBody(SubscriptionInvoice $invoice, string $kind): string
    {
        $planName = $invoice->plan?->name ?? ($invoice->metadata['plan_name'] ?? 'Business Package');
        $amount = $invoice->currency.' '.number_format((float) $invoice->total, 2);
        $due = $invoice->due_at?->format('d M Y') ?? now()->format('d M Y');
        $grace = $invoice->due_at?->copy()->addDays(2)->format('d M Y');
        $billingUrl = route('billing.index');

        $intro = match ($kind) {
            'renewal' => 'Your Bama business package is due for renewal in 5 days.',
            'grace' => 'Your Bama business package has expired and is now in the 2-day grace period.',
            'paid' => 'Your Bama business package payment has been received.',
            default => 'Your Bama business package invoice is ready.',
        };

        $body = "{$intro}\n\n"
            ."Client: {$invoice->customer_name}\n"
            ."Invoice: {$invoice->invoice_number}\n"
            ."Package: {$planName}\n"
            ."Amount: {$amount}\n"
            ."Due date: {$due}\n";

        if ($kind === 'paid') {
            return $body
                ."Paid at: ".($invoice->paid_at?->format('d M Y H:i') ?? now()->format('d M Y H:i'))."\n\n"
                ."Your workspace subscription is active.\n\n"
                ."Bama Solutions";
        }

        return $body
            ."Grace ends: {$grace}\n\n"
            ."Pay by card, M-PESA STK Push, or PayPal here:\n{$billingUrl}\n\n"
            ."If payment is not received by the end of the grace period, the workspace is locked automatically until renewal is completed.\n\n"
            ."Bama Solutions";
    }
}
