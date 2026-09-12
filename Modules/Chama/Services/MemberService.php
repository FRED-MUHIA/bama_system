<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\Member;
use Modules\Chama\Models\MemberExit;

class MemberService
{
    public function __construct(
        private ChamaNumberService $numbers,
        private ChamaAuditService $audit,
    ) {}

    public function create(array $data): Member
    {
        return DB::transaction(function () use ($data) {
            $member = Member::create(array_merge($data, [
                'member_number' => $data['member_number'] ?? $this->numbers->memberNumber(),
                'status' => $data['status'] ?? 'Applicant',
                'join_date' => $data['join_date'] ?? now()->toDateString(),
            ]));

            $this->audit->record('member.created', $member);

            return $member;
        });
    }

    public function approve(Member $member): Member
    {
        return DB::transaction(function () use ($member) {
            $old = $member->attributesToArray();
            $member->update([
                'status' => 'Active',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->audit->record('member.approved', $member, $old, $member->fresh()->attributesToArray(), $member->member_number);

            return $member->refresh();
        });
    }

    public function statement(Member $member): array
    {
        $contributions = $member->contributions()->latest('payment_date')->get()->map(fn ($row) => [
            'date' => $row->payment_date?->toDateString(),
            'type' => 'Contribution',
            'reference' => $row->contribution_number,
            'description' => $row->contributionType?->name ?? 'Contribution',
            'debit' => 0,
            'credit' => (float) $row->amount_paid,
        ]);

        $savings = $member->savingsAccounts()->with('transactions')->get()->flatMap(fn ($account) => $account->transactions->map(fn ($row) => [
            'date' => $row->transaction_date?->toDateString(),
            'type' => 'Savings',
            'reference' => $row->transaction_number,
            'description' => $row->transaction_type.' - '.$account->account_type,
            'debit' => in_array($row->transaction_type, ['Withdrawal', 'Transfer Out'], true) ? (float) $row->amount : 0,
            'credit' => in_array($row->transaction_type, ['Withdrawal', 'Transfer Out'], true) ? 0 : (float) $row->amount,
        ]));

        $loans = $member->loans()->with('repayments')->get()->flatMap(fn ($loan) => collect([
            [
                'date' => $loan->disbursement_date?->toDateString() ?? $loan->application_date?->toDateString(),
                'type' => 'Loan',
                'reference' => $loan->loan_number,
                'description' => $loan->purpose ?: 'Loan principal',
                'debit' => (float) $loan->principal,
                'credit' => 0,
            ],
        ])->merge($loan->repayments->map(fn ($row) => [
            'date' => $row->payment_date?->toDateString(),
            'type' => 'Loan Repayment',
            'reference' => $row->repayment_number,
            'description' => 'Loan repayment',
            'debit' => 0,
            'credit' => (float) $row->amount,
        ])));

        $fines = $member->fines()->latest('fine_date')->get()->map(fn ($row) => [
            'date' => $row->fine_date?->toDateString(),
            'type' => 'Fine',
            'reference' => $row->fine_number,
            'description' => $row->fine_type,
            'debit' => max((float) $row->amount - (float) $row->amount_paid, 0),
            'credit' => (float) $row->amount_paid,
        ]);

        $rows = $contributions->merge($savings)->merge($loans)->merge($fines)
            ->sortBy('date')
            ->values();

        $running = 0;
        $rows = $rows->map(function (array $row) use (&$running) {
            $running += (float) $row['credit'] - (float) $row['debit'];
            $row['running_total'] = $running;

            return $row;
        });

        return [
            'member' => $member,
            'rows' => $rows->all(),
            'closing_position' => $running,
        ];
    }

    public function startExit(Member $member, array $data): MemberExit
    {
        return DB::transaction(function () use ($member, $data) {
            $snapshot = $this->exitSnapshot($member);
            $exit = MemberExit::create(array_merge($snapshot, $data, [
                'member_id' => $member->id,
                'exit_number' => $data['exit_number'] ?? $this->numbers->memberExitNumber(),
                'status' => $data['status'] ?? 'Draft',
            ]));

            $this->audit->record('member.exit.started', $exit);

            return $exit;
        });
    }

    public function exitSnapshot(Member $member): array
    {
        $savings = (float) $member->savingsAccounts()->sum('current_balance');
        $shares = (float) $member->shareTransactions()->sum('share_value');
        $loan = (float) $member->loans()->sum(DB::raw('outstanding_principal + outstanding_interest + outstanding_penalties'));
        $guarantees = (float) $member->guaranteedLoans()->whereIn('status', ['Pending', 'Confirmed'])->sum('exposure_amount');
        $fines = (float) $member->fines()->sum(DB::raw('amount - amount_paid'));
        $contributions = (float) $member->contributionSchedules()->sum('balance');
        $dividends = (float) $member->dividendAllocations()->where('payment_status', 'Pending')->sum('net_dividend');
        $net = $savings + $shares + $dividends - $loan - $guarantees - $fines - $contributions;

        return [
            'savings_balance' => $savings,
            'share_capital' => $shares,
            'outstanding_loan' => $loan,
            'guaranteed_loans' => $guarantees,
            'fines' => $fines,
            'pending_contributions' => $contributions,
            'dividend_entitlement' => $dividends,
            'exit_fees' => 0,
            'net_settlement' => $net,
        ];
    }
}
