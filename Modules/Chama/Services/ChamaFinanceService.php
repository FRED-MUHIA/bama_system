<?php

namespace Modules\Chama\Services;

use App\Models\JournalEntry;
use App\Services\FinanceService;
use App\Support\SchemaCache;
use Modules\Chama\Models\Contribution;
use Modules\Chama\Models\LoanRepayment;

class ChamaFinanceService
{
    public function chartOfAccounts(): array
    {
        return [
            'assets' => ['Cash', 'Bank', 'M-PESA', 'Member Loans', 'Investments'],
            'liabilities' => ['Member Savings', 'Member Deposits', 'Payables'],
            'equity' => ['Share Capital', 'Retained Surplus', 'Reserves'],
            'income' => ['Loan Interest', 'Fines', 'Investment Income', 'Membership Fees', 'Other Income'],
            'expenses' => ['Administration', 'Bank Charges', 'Communication', 'Welfare Expense', 'Investment Expense', 'Audit', 'Legal'],
        ];
    }

    public function postContribution(Contribution $contribution): ?JournalEntry
    {
        if (! SchemaCache::hasTable('journal_entries')) {
            return null;
        }

        $finance = app(FinanceService::class);
        if (! $finance->ready()) {
            return null;
        }

        $entry = $finance->post([
            'entry_date' => $contribution->payment_date ?: now()->toDateString(),
            'description' => 'Chama contribution '.$contribution->contribution_number,
            'source_type' => $contribution::class,
            'source_id' => $contribution->id,
        ], [
            ['finance_account_id' => $finance->account('1000')->id, 'debit' => $contribution->amount_paid, 'credit' => 0],
            ['finance_account_id' => $finance->account('4200')->id, 'debit' => 0, 'credit' => $contribution->amount_paid],
        ]);

        $contribution->update(['journal_entry_id' => $entry->id]);

        return $entry;
    }

    public function postLoanRepayment(LoanRepayment $repayment): ?JournalEntry
    {
        if (! SchemaCache::hasTable('journal_entries')) {
            return null;
        }

        $finance = app(FinanceService::class);
        if (! $finance->ready()) {
            return null;
        }

        $entry = $finance->post([
            'entry_date' => $repayment->payment_date ?: now()->toDateString(),
            'description' => 'Chama loan repayment '.$repayment->repayment_number,
            'source_type' => $repayment::class,
            'source_id' => $repayment->id,
        ], [
            ['finance_account_id' => $finance->account('1000')->id, 'debit' => $repayment->amount, 'credit' => 0],
            ['finance_account_id' => $finance->account('4200')->id, 'debit' => 0, 'credit' => $repayment->interest_amount + $repayment->penalty_amount],
            ['finance_account_id' => $finance->account('1200')->id, 'debit' => 0, 'credit' => $repayment->principal_amount],
        ]);

        $repayment->update(['journal_entry_id' => $entry->id]);

        return $entry;
    }
}
