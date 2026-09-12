<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\Contribution;
use Modules\Chama\Models\ContributionSchedule;
use Modules\Chama\Models\DividendAllocation;
use Modules\Chama\Models\Fine;
use Modules\Chama\Models\Investment;
use Modules\Chama\Models\Loan;
use Modules\Chama\Models\LoanRepayment;
use Modules\Chama\Models\Meeting;
use Modules\Chama\Models\Member;
use Modules\Chama\Models\SavingsAccount;
use Modules\Chama\Models\TableBankingSession;
use Modules\Chama\Models\WelfareRequest;

class ChamaDashboardService
{
    public function metrics(): array
    {
        $welfareContributions = Contribution::whereHas('contributionType', fn ($query) => $query->where('fund_bucket', 'Welfare'))->sum('amount_paid');
        $welfarePaid = WelfareRequest::whereIn('status', ['Approved', 'Paid'])->sum('amount_approved');
        $latestTableSession = TableBankingSession::latest('session_date')->first();

        return [
            'total_members' => Member::count(),
            'active_members' => Member::where('status', 'Active')->count(),
            'total_contributions' => (float) Contribution::sum('amount_paid'),
            'contributions_this_month' => (float) Contribution::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount_paid'),
            'total_savings' => (float) SavingsAccount::sum('current_balance'),
            'outstanding_loans' => (float) Loan::sum(DB::raw('outstanding_principal + outstanding_interest + outstanding_penalties')),
            'loans_disbursed' => (float) Loan::whereIn('status', ['Active', 'In Arrears', 'Cleared'])->sum('approved_amount'),
            'loan_repayments' => (float) LoanRepayment::sum('amount'),
            'welfare_fund_balance' => (float) $welfareContributions - (float) $welfarePaid,
            'investment_value' => (float) Investment::sum('current_value'),
            'cash_balance' => (float) ($latestTableSession?->closing_balance ?? 0),
            'bank_balance' => 0,
            'mpesa_collections' => (float) Contribution::where('payment_method', 'M-PESA')->sum('amount_paid'),
            'fines_outstanding' => (float) Fine::sum(DB::raw('amount - amount_paid')),
            'dividends_payable' => (float) DividendAllocation::where('payment_status', 'Pending')->sum('net_dividend'),
            'meetings_this_month' => Meeting::whereMonth('meeting_date', now()->month)->whereYear('meeting_date', now()->year)->count(),
        ];
    }

    public function alerts(): array
    {
        return collect([
            ContributionSchedule::whereDate('due_date', '<=', now()->addDays(7))->whereIn('status', ['Pending', 'Partial'])->exists() ? ['type' => 'Contribution Due', 'severity' => 'Moderate'] : null,
            ContributionSchedule::whereDate('due_date', '<', today())->whereIn('status', ['Pending', 'Partial'])->exists() ? ['type' => 'Member In Arrears', 'severity' => 'High'] : null,
            Loan::where('status', 'In Arrears')->exists() ? ['type' => 'Loan Repayment Overdue', 'severity' => 'High'] : null,
            Meeting::whereDate('meeting_date', '>=', today())->whereDate('meeting_date', '<=', now()->addDays(7))->exists() ? ['type' => 'Meeting Upcoming', 'severity' => 'Low'] : null,
            WelfareRequest::where('status', 'Pending')->exists() ? ['type' => 'Welfare Request Pending', 'severity' => 'Moderate'] : null,
            Loan::whereIn('status', ['Submitted', 'Under Review'])->exists() ? ['type' => 'Loan Awaiting Approval', 'severity' => 'Moderate'] : null,
            Fine::whereIn('status', ['Pending', 'Appealed'])->exists() ? ['type' => 'Fine Outstanding', 'severity' => 'Low'] : null,
            DividendAllocation::where('payment_status', 'Pending')->exists() ? ['type' => 'Dividend Pending', 'severity' => 'Moderate'] : null,
            Investment::where('status', 'Maturing')->exists() ? ['type' => 'Investment Maturity', 'severity' => 'Moderate'] : null,
            TableBankingSession::where('is_balanced', false)->exists() ? ['type' => 'Bank Reconciliation Required', 'severity' => 'High'] : null,
        ])->filter()->values()->all();
    }

    public function charts(): array
    {
        return [
            'monthly_contributions' => $this->monthlySeries(Contribution::get(['payment_date', 'amount_paid']), 'payment_date', 'amount_paid'),
            'member_growth' => $this->monthlySeries(Member::get(['created_at']), 'created_at'),
            'loan_repayment_trend' => $this->monthlySeries(LoanRepayment::get(['payment_date', 'amount']), 'payment_date', 'amount'),
        ];
    }

    private function monthlySeries($records, string $dateColumn, ?string $amountColumn = null): array
    {
        return $records
            ->filter(fn ($record) => $record->{$dateColumn})
            ->groupBy(fn ($record) => $record->{$dateColumn}->format('Y-m'))
            ->map(fn ($group) => $amountColumn ? (float) $group->sum($amountColumn) : $group->count())
            ->sortKeys()
            ->all();
    }
}
