<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairSubscriptionPeriodsCommand extends Command
{
    protected $signature = 'subscriptions:repair-periods {--apply : Save the reviewed corrections} {--tenant= : Limit to one tenant ID}';

    protected $description = 'Preview or repair existing trial and 30-day paid subscription dates from payment history.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Applying subscription corrections.' : 'Preview only; no records will be changed.');

        Subscription::withoutGlobalScopes()
            ->when($this->option('tenant'), fn ($query, $tenant) => $query->where('tenant_id', $tenant))
            ->select('id')->chunkById(100, function ($rows) use ($apply) {
                foreach ($rows as $row) {
                    DB::transaction(function () use ($row, $apply) {
                        $subscription = Subscription::withoutGlobalScopes()->lockForUpdate()->findOrFail($row->id);
                        if ($subscription->status === 'cancelled' || $subscription->hasAdminAccessPeriod()) {
                            return;
                        }

                        $invoices = $subscription->invoices()->where('status', 'paid')->with('payments')->get();
                        $dates = [];
                        foreach ($invoices as $invoice) {
                            $payment = $invoice->payments->firstWhere('id', data_get($invoice->metadata, 'paid_payment_id'));
                            if (! $payment && $invoice->payments->count() === 1) {
                                $payment = $invoice->payments->first();
                            }
                            if (! $payment?->isSuccessful() || ! $payment->paid_at
                                || (int) $payment->tenant_id !== (int) $subscription->tenant_id
                                || (float) $payment->amount !== (float) $invoice->total
                                || strtoupper($payment->currency) !== strtoupper($invoice->currency)
                                || data_get($invoice->metadata, 'period', 'monthly') !== 'monthly') {
                                $this->warn("Subscription {$subscription->id}: skipped; payment history needs review.");
                                return;
                            }
                            $dates[] = $payment->paid_at;
                        }

                        $renewal = null;
                        foreach (collect($dates)->sortBy(fn ($date) => $date->getTimestamp()) as $paidAt) {
                            $renewal = ($renewal && $renewal->isAfter($paidAt) ? $renewal : $paidAt)->copy()->addDays(30);
                        }

                        // Without a payment history, only remove the old trial's artificial renewal date.
                        if (! $dates && (! $subscription->trial_ends_at || ! in_array($subscription->status, ['trialing', 'past_due', 'paused'], true))) {
                            $this->warn("Subscription {$subscription->id}: skipped; no paid history or identifiable trial.");
                            return;
                        }
                        $old = $subscription->renews_at?->toDateTimeString();
                        $new = $renewal?->toDateTimeString();
                        if ($old === $new) {
                            return;
                        }
                        $this->line("Tenant {$subscription->tenant_id}, subscription {$subscription->id}: ".($old ?? 'none').' -> '.($new ?? 'trial only'));
                        if (! $apply) {
                            return;
                        }

                        $metadata = $subscription->metadata ?? [];
                        $metadata['period_repairs'][] = [
                            'at' => now()->toIso8601String(),
                            'previous_renews_at' => $old,
                            'renews_at' => $new,
                            'previous_grace_ends_at' => $subscription->grace_ends_at?->toDateTimeString(),
                        ];
                        $subscription->forceFill([
                            'renews_at' => $renewal,
                            'grace_ends_at' => $subscription->grace_ends_at
                                ? ($renewal ?: $subscription->trial_ends_at)->copy()->addDays(2)
                                : null,
                            'last_renewal_notice_sent_at' => null,
                            'last_grace_notice_sent_at' => null,
                            'metadata' => $metadata,
                        ])->save();
                    });
                }
            });

        return self::SUCCESS;
    }
}
