<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Chama\Models\SavingsAccount;
use Modules\Chama\Models\SavingsTransaction;

class SavingsService
{
    public function __construct(
        private ChamaNumberService $numbers,
        private ChamaAuditService $audit,
    ) {}

    public function openAccount(array $data): SavingsAccount
    {
        return DB::transaction(function () use ($data) {
            $opening = (float) ($data['opening_balance'] ?? 0);
            $account = SavingsAccount::create(array_merge($data, [
                'account_number' => $data['account_number'] ?? $this->numbers->savingsAccountNumber(),
                'current_balance' => $data['current_balance'] ?? $opening,
            ]));

            $this->audit->record('savings_account.opened', $account);

            return $account;
        });
    }

    public function recordTransaction(SavingsAccount $account, array $data): SavingsTransaction
    {
        return DB::transaction(function () use ($account, $data) {
            $account = SavingsAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $amount = (float) ($data['amount'] ?? 0);
            $type = $data['transaction_type'];
            $direction = in_array($type, ['Withdrawal', 'Transfer Out'], true) ? -1 : 1;
            $balance = (float) $account->current_balance + ($direction * $amount);

            if ($balance < -0.005) {
                throw ValidationException::withMessages(['amount' => 'Savings withdrawal cannot exceed the current account balance.']);
            }

            $account->update(['current_balance' => $balance]);

            $transaction = SavingsTransaction::create([
                'savings_account_id' => $account->id,
                'member_id' => $account->member_id,
                'transaction_number' => $data['transaction_number'] ?? $this->numbers->savingsTransactionNumber(),
                'transaction_type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? 'Posted',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->record('savings_transaction.posted', $transaction);

            return $transaction;
        });
    }
}
