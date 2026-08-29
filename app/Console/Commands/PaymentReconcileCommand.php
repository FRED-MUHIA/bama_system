<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentReconciliationService;
use Illuminate\Console\Command;

class PaymentReconcileCommand extends Command
{
    protected $signature = 'payments:reconcile {--gateway=} {--status=pending} {--limit=100}';

    protected $description = 'Query gateways for unsettled subscription payments and reconcile verified statuses.';

    public function handle(PaymentReconciliationService $reconciliation): int
    {
        $stats = $reconciliation->reconcile(
            $this->option('gateway') ?: null,
            $this->option('status') ?: null,
            (int) $this->option('limit')
        );

        $this->info("Payments reconciled. Checked: {$stats['checked']}. Successful: {$stats['successful']}. Failed: {$stats['failed']}. Errors: {$stats['errors']}.");

        return self::SUCCESS;
    }
}
