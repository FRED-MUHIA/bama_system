<?php

namespace App\Services;

use App\Models\FinanceAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\SupplierInvoice;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public const ACCOUNT_TYPES = ['Asset', 'Liability', 'Equity', 'Revenue', 'Cost of Sales', 'Expense', 'Other Income', 'Other Expense'];

    public const ACCOUNTS = [
        ['1000', 'Cash', 'Asset', 'Cash'],
        ['1100', 'Bank Accounts', 'Asset', 'Bank'],
        ['1200', 'Accounts Receivable', 'Asset', 'Receivable'],
        ['1300', 'Inventory', 'Asset', 'Inventory'],
        ['1400', 'Fixed Assets', 'Asset', 'Fixed Asset'],
        ['1490', 'Accumulated Depreciation', 'Asset', 'Contra Asset'],
        ['2000', 'Accounts Payable', 'Liability', 'Payable'],
        ['2100', 'Loans', 'Liability', 'Loan'],
        ['2200', 'VAT Payable', 'Liability', 'Tax'],
        ['2210', 'Input VAT', 'Asset', 'Tax'],
        ['2300', 'Withholding Tax Payable', 'Liability', 'Tax'],
        ['3000', 'Owner Equity', 'Equity', 'Equity'],
        ['3100', 'Retained Earnings', 'Equity', 'Equity'],
        ['4000', 'Product Sales', 'Revenue', 'Sales'],
        ['4100', 'Service Revenue', 'Revenue', 'Sales'],
        ['4200', 'Other Income', 'Other Income', 'Income'],
        ['5000', 'Salaries', 'Expense', 'Payroll'],
        ['5100', 'Utilities', 'Expense', 'Operating'],
        ['5200', 'Marketing', 'Expense', 'Operating'],
        ['5300', 'Office Expenses', 'Expense', 'Operating'],
        ['5400', 'Travel', 'Expense', 'Operating'],
        ['5500', 'Procurement & Project Costs', 'Cost of Sales', 'Project Costs'],
        ['5600', 'Depreciation Expense', 'Expense', 'Depreciation'],
    ];

    public function ready(): bool
    {
        return Schema::hasTable('finance_accounts') && Schema::hasTable('journal_entries') && Schema::hasTable('journal_lines');
    }

    public function seedAccounts(): void
    {
        if (! Schema::hasTable('finance_accounts')) {
            return;
        }

        foreach (self::ACCOUNTS as [$code, $name, $type, $subtype]) {
            FinanceAccount::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'type' => $type, 'subtype' => $subtype, 'is_system' => true]
            );
        }
    }

    public function account(string $code): FinanceAccount
    {
        if (! Schema::hasTable('finance_accounts')) {
            throw ValidationException::withMessages(['finance' => 'Finance tables are not installed.']);
        }

        $this->seedAccounts();

        return FinanceAccount::where('code', $code)->firstOrFail();
    }

    public function post(array $data, array $lines, bool $approve = true): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines, $approve) {
            $date = $data['entry_date'] ?? now()->toDateString();
            $businessId = $data['business_id'] ?? ActiveBusiness::id();
            $locked = Schema::hasTable('finance_periods')
                && DB::table('finance_periods')
                    ->where('business_id', $businessId)
                    ->where('status', 'Closed')
                    ->whereDate('starts_at', '<=', $date)
                    ->whereDate('ends_at', '>=', $date)
                    ->exists();

            if ($locked) {
                throw ValidationException::withMessages(['entry_date' => 'This accounting period is closed.']);
            }

            $debit = collect($lines)->sum(fn ($line) => (float) ($line['debit'] ?? 0));
            $credit = collect($lines)->sum(fn ($line) => (float) ($line['credit'] ?? 0));
            if ($debit <= 0 || abs($debit - $credit) > .005) {
                throw ValidationException::withMessages(['lines' => 'Journal debits and credits must be equal and greater than zero.']);
            }

            if (! empty($data['source_type']) && isset($data['source_id'])) {
                $existing = JournalEntry::withoutGlobalScope('business')
                    ->where('source_type', $data['source_type'])
                    ->where('source_id', $data['source_id'])
                    ->when($businessId, fn ($query) => $query->where('business_id', $businessId))
                    ->first();

                if ($existing) {
                    return $this->refreshSourceJournal($existing, $data, $lines, $approve);
                }
            }

            $entry = JournalEntry::create($data + [
                'business_id' => $businessId,
                'entry_number' => $this->nextNumber($businessId),
                'status' => $approve ? 'Posted' : 'Draft',
                'created_by' => auth()->id(),
                'approved_by' => $approve ? auth()->id() : null,
                'approved_at' => $approve ? now() : null,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            return $entry->load('lines.account');
        });
    }

    public function reverse(JournalEntry $entry, string $reason): JournalEntry
    {
        if ($entry->status === 'Reversed') {
            throw ValidationException::withMessages(['entry' => 'Journal already reversed.']);
        }

        $reversal = $this->post([
            'entry_date' => now(),
            'description' => 'Reversal: '.$entry->description,
            'reversal_of_id' => $entry->id,
            'reason' => $reason,
        ], $entry->lines->map(fn ($line) => [
            'finance_account_id' => $line->finance_account_id,
            'department_id' => $line->department_id,
            'cost_center_id' => $line->cost_center_id,
            'project_id' => $line->project_id,
            'description' => 'Reversal',
            'debit' => $line->credit,
            'credit' => $line->debit,
        ])->all());

        $entry->update([
            'status' => 'Reversed',
            'reversed_by' => auth()->id(),
            'reversed_at' => now(),
            'reason' => $reason,
        ]);

        return $reversal;
    }

    public function unreverse(JournalEntry $entry, string $reason): void
    {
        DB::transaction(function () use ($entry, $reason) {
            $entry = JournalEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            if ($entry->status !== 'Reversed') {
                throw ValidationException::withMessages(['entry' => 'Only a reversed journal can be restored.']);
            }

            $reversal = JournalEntry::where('reversal_of_id', $entry->id)
                ->where('status', 'Posted')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $reversal) {
                throw ValidationException::withMessages(['entry' => 'The posted reversal entry could not be found.']);
            }

            $entry->update([
                'status' => 'Posted',
                'reversed_by' => null,
                'reversed_at' => null,
                'reason' => trim(($entry->reason ? ($entry->reason."\n") : '').'Restored: '.$reason),
            ]);

            $reversal->update([
                'status' => 'Reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reason' => trim(($reversal->reason ? ($reversal->reason."\n") : '').'Cancelled restoration: '.$reason),
            ]);
        });
    }

    public function postInvoice(Invoice $invoice): ?JournalEntry
    {
        if (! Schema::hasTable('journal_entries') || $invoice->total <= 0 || $invoice->isAllocationInvoice()) {
            return null;
        }

        $net = max((float) $invoice->total - (float) $invoice->tax_total, 0);
        $tags = $this->tags($invoice->project_id);
        $lines = [
            ['finance_account_id' => $this->account('1200')->id, 'debit' => $invoice->total, 'credit' => 0] + $tags,
            ['finance_account_id' => $this->account('4100')->id, 'debit' => 0, 'credit' => $net] + $tags,
        ];

        if ($invoice->tax_total > 0) {
            $lines[] = ['finance_account_id' => $this->account('2200')->id, 'debit' => 0, 'credit' => $invoice->tax_total] + $tags;
        }

        return $this->post([
            'business_id' => $invoice->business_id,
            'entry_date' => $invoice->invoice_date,
            'description' => 'Invoice '.$invoice->invoice_number,
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
        ], $lines);
    }

    public function postPayment($payment): ?JournalEntry
    {
        if (! Schema::hasTable('journal_entries') || $payment->amount <= 0) {
            return null;
        }

        $tags = $this->tags($payment->invoice?->project_id);

        return $this->post([
            'business_id' => $payment->business_id,
            'entry_date' => $payment->payment_date,
            'description' => 'Customer payment '.($payment->reference ?: '#'.$payment->id),
            'source_type' => $payment::class,
            'source_id' => $payment->id,
        ], [
            ['finance_account_id' => $this->account('1000')->id, 'debit' => $payment->amount, 'credit' => 0] + $tags,
            ['finance_account_id' => $this->account('1200')->id, 'debit' => 0, 'credit' => $payment->amount] + $tags,
        ]);
    }

    public function postSupplierInvoice(SupplierInvoice $bill): ?JournalEntry
    {
        if (! Schema::hasTable('journal_entries') || $bill->total <= 0) {
            return null;
        }

        $tags = $this->tags($bill->project_id);

        return $this->post([
            'business_id' => $bill->business_id,
            'entry_date' => $bill->invoice_date ?: now(),
            'description' => 'Supplier bill '.$bill->invoice_number,
            'source_type' => SupplierInvoice::class,
            'source_id' => $bill->id,
        ], [
            ['finance_account_id' => $this->account('5500')->id, 'debit' => $bill->total, 'credit' => 0] + $tags,
            ['finance_account_id' => $this->account('2000')->id, 'debit' => 0, 'credit' => $bill->total] + $tags,
        ]);
    }

    public function postSupplierPayment($payment): ?JournalEntry
    {
        if (! Schema::hasTable('journal_entries') || $payment->amount <= 0) {
            return null;
        }

        $tags = $this->tags($payment->supplierInvoice?->project_id);

        return $this->post([
            'business_id' => $payment->business_id,
            'entry_date' => $payment->payment_date,
            'description' => 'Supplier payment '.($payment->reference ?: '#'.$payment->id),
            'source_type' => $payment::class,
            'source_id' => $payment->id,
        ], [
            ['finance_account_id' => $this->account('2000')->id, 'debit' => $payment->amount, 'credit' => 0] + $tags,
            ['finance_account_id' => $this->account('1000')->id, 'debit' => 0, 'credit' => $payment->amount] + $tags,
        ]);
    }

    public function syncLegacy(): array
    {
        $counts = [];
        $records = app(FinanceRecordSyncService::class);

        foreach (Invoice::source()->get() as $invoice) {
            $invoice = $records->syncInvoice($invoice, true);
            $this->postInvoice($invoice);
            $counts['invoices'] = ($counts['invoices'] ?? 0) + 1;
        }

        foreach (\App\Models\Payment::with('invoice')->get() as $payment) {
            $this->postPayment($payment);
            $records->syncInvoiceId($payment->invoice_id, $payment->business_id, true);
            $counts['payments'] = ($counts['payments'] ?? 0) + 1;
        }

        foreach (SupplierInvoice::get() as $bill) {
            $bill = $records->syncSupplierInvoice($bill, true);
            $this->postSupplierInvoice($bill);
            $counts['bills'] = ($counts['bills'] ?? 0) + 1;
        }

        foreach (\App\Models\SupplierPayment::with('supplierInvoice')->get() as $payment) {
            $this->postSupplierPayment($payment);
            $records->syncSupplierInvoiceId($payment->supplier_invoice_id, $payment->business_id, true);
            $counts['supplier_payments'] = ($counts['supplier_payments'] ?? 0) + 1;
        }

        return $counts;
    }

    public function reports(): array
    {
        if (! $this->ready()) {
            $lines = collect();
            $income = $expenses = $assets = $liabilities = $equity = 0;

            return compact('lines', 'income', 'expenses', 'assets', 'liabilities', 'equity');
        }

        $lines = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('finance_accounts', 'finance_accounts.id', '=', 'journal_lines.finance_account_id')
            ->where('journal_entries.business_id', ActiveBusiness::id())
            ->where('journal_entries.status', 'Posted')
            ->select(
                'finance_accounts.id',
                'finance_accounts.code',
                'finance_accounts.name',
                'finance_accounts.type',
                DB::raw('SUM(journal_lines.debit) debit'),
                DB::raw('SUM(journal_lines.credit) credit')
            )
            ->groupBy('finance_accounts.id', 'finance_accounts.code', 'finance_accounts.name', 'finance_accounts.type')
            ->orderBy('finance_accounts.code')
            ->get();

        $income = $lines->whereIn('type', ['Revenue', 'Other Income'])->sum(fn ($line) => $line->credit - $line->debit);
        $expenses = $lines->whereIn('type', ['Cost of Sales', 'Expense', 'Other Expense'])->sum(fn ($line) => $line->debit - $line->credit);
        $assets = $lines->where('type', 'Asset')->sum(fn ($line) => $line->debit - $line->credit);
        $liabilities = $lines->where('type', 'Liability')->sum(fn ($line) => $line->credit - $line->debit);
        $equity = $lines->where('type', 'Equity')->sum(fn ($line) => $line->credit - $line->debit);

        return compact('lines', 'income', 'expenses', 'assets', 'liabilities', 'equity');
    }

    public function aging($records, string $dueField, string $balanceField): array
    {
        $buckets = ['Current' => 0, '30 Days' => 0, '60 Days' => 0, '90 Days' => 0, '120+ Days' => 0];

        foreach ($records as $record) {
            $days = max(0, now()->startOfDay()->diffInDays($record->{$dueField} ?: now(), false) * -1);
            $key = $days <= 0 ? 'Current' : ($days <= 30 ? '30 Days' : ($days <= 60 ? '60 Days' : ($days <= 90 ? '90 Days' : '120+ Days')));
            $amount = $balanceField === 'outstanding'
                ? (float) ($record->outstanding_balance ?? max((float) $record->total - (float) $record->amount_paid, 0))
                : (float) $record->{$balanceField};

            $buckets[$key] += $amount;
        }

        return $buckets;
    }

    private function refreshSourceJournal(JournalEntry $entry, array $data, array $lines, bool $approve): JournalEntry
    {
        if ($entry->status === 'Reversed') {
            return $entry->load('lines.account');
        }

        $entry->forceFill(collect($data)->except('entry_number')->all() + [
            'status' => $approve ? 'Posted' : 'Draft',
            'approved_by' => $approve ? ($entry->approved_by ?: auth()->id()) : null,
            'approved_at' => $approve ? ($entry->approved_at ?: now()) : null,
        ])->save();

        $entry->lines()->delete();
        foreach ($lines as $line) {
            $entry->lines()->create($line);
        }

        return $entry->load('lines.account');
    }

    private function nextNumber(?int $businessId = null): string
    {
        $last = JournalEntry::withoutGlobalScopes()->where('business_id', $businessId ?? ActiveBusiness::id())->max('id') ?? 0;

        return 'JE-'.now()->format('Y').'-'.str_pad($last + 1, 6, '0', STR_PAD_LEFT);
    }

    private function tags(?int $projectId): array
    {
        $project = $projectId ? Project::with('costCenter')->find($projectId) : null;

        return [
            'project_id' => $projectId,
            'cost_center_id' => $project?->cost_center_id,
            'department_id' => $project?->costCenter?->department_id,
        ];
    }
}
