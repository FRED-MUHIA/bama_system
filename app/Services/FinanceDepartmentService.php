<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinanceDepartmentService
{
    public function cockpit(array $ledger, Collection $receivables, Collection $payables, Collection $banks): array
    {
        return [
            'industry' => $this->industryLabel(),
            'period' => [
                'month_start' => now()->startOfMonth(),
                'month_end' => now()->endOfMonth(),
            ],
            'scorecards' => $this->scorecards($ledger, $receivables, $payables, $banks),
            'industry_rows' => $this->industryRows(),
            'invoice_pipeline' => $this->invoicePipeline(),
            'cash_movement' => $this->cashMovement(),
            'bank_summary' => $this->bankSummary($banks),
            'tax_position' => $this->taxPosition($ledger),
            'risk_flags' => $this->riskFlags($receivables, $payables, $banks),
            'top_clients' => $this->topClients(),
            'top_suppliers' => $this->topSuppliers(),
        ];
    }

    private function scorecards(array $ledger, Collection $receivables, Collection $payables, Collection $banks): array
    {
        $income = (float) ($ledger['income'] ?? 0);
        $expenses = (float) ($ledger['expenses'] ?? 0);
        $profit = $income - $expenses;
        $cash = $banks->sum(fn ($bank) => $this->bankBalance($bank));

        return [
            'Gross Margin' => $income > 0 ? round(($profit / $income) * 100, 2) : 0,
            'Cash Position' => $cash,
            'Receivables Due' => (float) $receivables->sum('balance'),
            'Payables Due' => (float) $payables->sum(fn ($bill) => max((float) $bill->total - (float) $bill->amount_paid, 0)),
            'Working Capital' => $cash + (float) $receivables->sum('balance') - (float) $payables->sum(fn ($bill) => max((float) $bill->total - (float) $bill->amount_paid, 0)),
            'Ledger Profit' => $profit,
        ];
    }

    private function industryRows(): Collection
    {
        if (! Schema::hasTable('invoices')) {
            return collect();
        }

        return Invoice::source()
            ->with('client')
            ->get()
            ->groupBy(fn ($invoice) => $invoice->industry_module ?: 'shared')
            ->map(function (Collection $invoices, string $module) {
                return [
                    'module' => $module,
                    'label' => $this->moduleLabel($module),
                    'count' => $invoices->count(),
                    'revenue' => (float) $invoices->sum('total'),
                    'paid' => (float) $invoices->sum('amount_paid'),
                    'outstanding' => (float) $invoices->sum('balance'),
                    'overdue' => (float) $invoices->filter(fn ($invoice) => $invoice->due_date && $invoice->due_date->isPast() && (float) $invoice->balance > 0)->sum('balance'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    private function invoicePipeline(): Collection
    {
        if (! Schema::hasTable('invoices')) {
            return collect();
        }

        return Invoice::source()
            ->get()
            ->groupBy(fn ($invoice) => Str::title(str_replace('_', ' ', $invoice->payment_status ?: 'unpaid')))
            ->map(fn (Collection $invoices, string $status) => [
                'status' => $status,
                'count' => $invoices->count(),
                'total' => (float) $invoices->sum('total'),
                'balance' => (float) $invoices->sum('balance'),
            ])
            ->sortBy('status')
            ->values();
    }

    private function cashMovement(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $customerReceipts = Schema::hasTable('payments')
            ? (float) Payment::whereBetween('payment_date', [$start, $end])->sum('amount')
            : 0.0;

        $supplierPayments = Schema::hasTable('supplier_payments')
            ? (float) SupplierPayment::whereBetween('payment_date', [$start, $end])->sum('amount')
            : 0.0;

        $bankDeposits = Schema::hasTable('bank_transactions')
            ? (float) BankTransaction::whereBetween('transaction_date', [$start, $end])->whereIn('type', ['Deposit', 'Transfer In'])->sum('amount')
            : 0.0;

        $bankWithdrawals = Schema::hasTable('bank_transactions')
            ? (float) BankTransaction::whereBetween('transaction_date', [$start, $end])->whereIn('type', ['Withdrawal', 'Transfer Out'])->sum('amount')
            : 0.0;

        return [
            'Customer Receipts' => $customerReceipts,
            'Supplier Payments' => $supplierPayments,
            'Bank Deposits' => $bankDeposits,
            'Bank Withdrawals' => $bankWithdrawals,
            'Net Cash Movement' => $customerReceipts + $bankDeposits - $supplierPayments - $bankWithdrawals,
        ];
    }

    private function bankSummary(Collection $banks): Collection
    {
        return $banks->map(fn (BankAccount $bank) => [
            'name' => $bank->name,
            'type' => $bank->type,
            'balance' => $this->bankBalance($bank),
            'unreconciled' => $bank->transactions->where('is_reconciled', false)->count(),
            'currency' => $bank->currency,
        ]);
    }

    private function taxPosition(array $ledger): array
    {
        $lines = $ledger['lines'] ?? collect();
        $outputVat = (float) (($lines->firstWhere('code', '2200')?->credit ?? 0) - ($lines->firstWhere('code', '2200')?->debit ?? 0));
        $inputVat = (float) (($lines->firstWhere('code', '2210')?->debit ?? 0) - ($lines->firstWhere('code', '2210')?->credit ?? 0));

        return [
            'Output VAT' => max($outputVat, 0),
            'Input VAT' => max($inputVat, 0),
            'Net VAT Payable' => max($outputVat - $inputVat, 0),
        ];
    }

    private function riskFlags(Collection $receivables, Collection $payables, Collection $banks): array
    {
        $overdueReceivables = $receivables->filter(fn ($invoice) => $invoice->due_date && $invoice->due_date->isPast());
        $overduePayables = $payables->filter(fn ($bill) => $bill->due_date && $bill->due_date->isPast());
        $draftJournals = Schema::hasTable('journal_entries') ? JournalEntry::where('status', 'Draft')->count() : 0;
        $unreconciled = $banks->sum(fn ($bank) => $bank->transactions->where('is_reconciled', false)->count());

        return [
            ['label' => 'Overdue receivables', 'count' => $overdueReceivables->count(), 'amount' => (float) $overdueReceivables->sum('balance')],
            ['label' => 'Overdue payables', 'count' => $overduePayables->count(), 'amount' => (float) $overduePayables->sum(fn ($bill) => max((float) $bill->total - (float) $bill->amount_paid, 0))],
            ['label' => 'Unreconciled bank lines', 'count' => $unreconciled, 'amount' => null],
            ['label' => 'Draft journals', 'count' => $draftJournals, 'amount' => null],
        ];
    }

    private function topClients(): Collection
    {
        return Schema::hasTable('invoices')
            ? Invoice::source()->with('client')->get()->groupBy('client_id')->map(fn ($invoices) => [
                'name' => $invoices->first()->client?->name ?: 'Unknown',
                'revenue' => (float) $invoices->sum('total'),
                'outstanding' => (float) $invoices->sum('balance'),
            ])->sortByDesc('revenue')->take(8)->values()
            : collect();
    }

    private function topSuppliers(): Collection
    {
        return Schema::hasTable('supplier_invoices')
            ? SupplierInvoice::with('supplier')->get()->groupBy('supplier_id')->map(fn ($bills) => [
                'name' => $bills->first()->supplier?->name ?: 'Unknown',
                'spend' => (float) $bills->sum('total'),
                'outstanding' => (float) $bills->sum(fn ($bill) => max((float) $bill->total - (float) $bill->amount_paid, 0)),
            ])->sortByDesc('spend')->take(8)->values()
            : collect();
    }

    private function bankBalance(BankAccount $bank): float
    {
        return (float) $bank->opening_balance
            + (float) $bank->transactions->whereIn('type', ['Deposit', 'Transfer In'])->sum('amount')
            - (float) $bank->transactions->whereIn('type', ['Withdrawal', 'Transfer Out'])->sum('amount');
    }

    private function industryLabel(): string
    {
        $industry = ActiveBusiness::current()?->industry ?: ActiveTenant::current()?->industry ?: 'shared';

        return $this->moduleLabel($industry);
    }

    private function moduleLabel(string $module): string
    {
        return match ($module) {
            'printing_branding' => 'Printing & Branding',
            'real_estate' => 'Real Estate',
            'agriculture' => 'Agriculture',
            'hospitality' => 'Hospitality',
            'retail' => 'Retail',
            'fitness' => 'Fitness',
            'salon' => 'Salon & Spa',
            'construction' => 'Construction',
            'shared' => 'Shared / General',
            default => Str::headline(str_replace('-', '_', $module)),
        };
    }
}
