<?php

namespace App\Services\Billing;

use App\Mail\SubscriptionReceiptMail;
use App\Models\SubscriptionInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SubscriptionReceiptService
{
    public function send(SubscriptionInvoice $invoice): int
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = SubscriptionInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payment = $invoice->payments()->find(data_get($invoice->metadata, 'paid_payment_id'));
            if ($invoice->status !== 'paid' || ! $payment?->isSuccessful()) {
                return 0;
            }

            $metadata = $invoice->metadata ?? [];
            $receipt = $metadata['subscription_receipt'] ?? null;
            if (! $receipt) {
                $receipt = [
                    'number' => 'BAMA-RCT-'.$invoice->id.'-'.$payment->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer_name,
                    'plan_name' => $metadata['plan_name'] ?? $invoice->plan?->name ?? 'Business Package',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'paid_at' => ($payment->paid_at ?: $invoice->paid_at)?->format('d M Y H:i'),
                    'timezone' => config('app.timezone'),
                    'provider' => strtoupper($payment->provider),
                    'reference' => $payment->provider_receipt ?: $payment->provider_payment_id ?: $payment->merchant_reference,
                    'renews_at' => $invoice->subscription()->withoutGlobalScopes()->first()?->renews_at?->format('d M Y H:i'),
                ];
                $metadata['subscription_receipt'] = $receipt;
                $invoice->forceFill(['metadata' => $metadata])->save();
            }

            $tenant = $invoice->tenant()->withTrashed()->first();
            $emails = $tenant ? app(SubscriptionBillingService::class)->billingEmails($tenant) : [];
            if (! $emails && $invoice->billing_email) {
                $emails = [$invoice->billing_email];
            }
            $subject = 'Bama subscription payment received — receipt '.$receipt['number'];
            $sent = 0;
            $pdf = null;
            foreach ($emails as $email) {
                if ($invoice->emailLogs()->where('recipient_email', $email)->where('subject', $subject)->where('status', 'sent')->exists()) {
                    continue;
                }
                try {
                    $pdf ??= Pdf::loadView('pdf.subscription-receipt', ['receipt' => $receipt])->setPaper('a4')->output();
                    Mail::to($email)->send(new SubscriptionReceiptMail($receipt, $pdf));
                    $invoice->emailLogs()->create([
                        'recipient_email' => $email, 'subject' => $subject,
                        'message' => 'Subscription receipt '.$receipt['number'].' attached as PDF.',
                        'status' => 'sent', 'sent_at' => now(),
                    ]);
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                    $invoice->emailLogs()->create([
                        'recipient_email' => $email, 'subject' => $subject,
                        'message' => 'Subscription receipt '.$receipt['number'],
                        'status' => 'failed', 'error' => $e->getMessage(),
                    ]);
                }
            }

            return $sent;
        });
    }
}
