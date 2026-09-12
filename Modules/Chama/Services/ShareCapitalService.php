<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\ShareTransaction;

class ShareCapitalService
{
    public function __construct(private ChamaNumberService $numbers, private ChamaAuditService $audit) {}

    public function record(array $data): ShareTransaction
    {
        return DB::transaction(function () use ($data) {
            $units = (float) ($data['share_units'] ?? 0);
            $price = (float) ($data['price_per_share'] ?? 0);
            $transaction = ShareTransaction::create(array_merge($data, [
                'transaction_number' => $data['transaction_number'] ?? $this->numbers->shareTransactionNumber(),
                'share_value' => $data['share_value'] ?? round($units * $price, 2),
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'Posted',
            ]));

            $this->audit->record('share_capital.transaction.recorded', $transaction);

            return $transaction;
        });
    }
}
