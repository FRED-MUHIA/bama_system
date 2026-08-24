<?php

namespace App\Console\Commands;

use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SubscriptionBillingSweepCommand extends Command
{
    protected $signature = 'subscriptions:billing-sweep {--date= : Date to evaluate in YYYY-MM-DD format}';

    protected $description = 'Send BAMA subscription renewal reminders and lock profiles after grace period.';

    public function handle(SubscriptionBillingService $billing): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $stats = $billing->sweep($date);

        $this->info("BAMA subscription billing processed for {$date->toDateString()}.");
        $this->line('Renewal notices: '.$stats['renewal_notices']);
        $this->line('Grace notices: '.$stats['grace_notices']);
        $this->line('Locked profiles: '.$stats['locked']);

        return self::SUCCESS;
    }
}
