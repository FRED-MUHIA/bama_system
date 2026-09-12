<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Chama\Models\Loan;
use Modules\Chama\Models\LoanProduct;
use Modules\Chama\Models\LoanRepayment;
use Modules\Chama\Models\LoanSchedule;
use Modules\Chama\Models\Member;

class ChamaLoanService
{
    public function __construct(
        private ChamaNumberService $numbers,
        private ChamaAuditService $audit,
    ) {}

    public function createProduct(array $data): LoanProduct
    {
        return DB::transaction(function () use ($data) {
            $product = LoanProduct::create(array_merge($data, [
                'product_number' => $data['product_number'] ?? $this->numbers->loanProductNumber(),
            ]));

            $this->audit->record('loan_product.created', $product);

            return $product;
        });
    }

    public function apply(array $data): Loan
    {
        return DB::transaction(function () use ($data) {
            $product = LoanProduct::find($data['loan_product_id'] ?? null);
            $member = Member::findOrFail($data['member_id']);
            $principal = (float) $data['principal'];
            $eligibility = $product ? $this->eligibility($member, $product, $principal) : ['eligible' => true, 'checks' => []];

            $loan = Loan::create([
                'loan_product_id' => $product?->id,
                'member_id' => $member->id,
                'loan_number' => $data['loan_number'] ?? $this->numbers->loanNumber(),
                'principal' => $principal,
                'interest_rate' => $data['interest_rate'] ?? $product?->interest_rate ?? 0,
                'interest_method' => $data['interest_method'] ?? $product?->interest_method ?? 'Flat Rate',
                'term_months' => $data['term_months'] ?? $product?->term_months ?? 1,
                'application_date' => $data['application_date'] ?? now()->toDateString(),
                'purpose' => $data['purpose'] ?? null,
                'status' => $data['status'] ?? 'Submitted',
                'eligibility_result' => $eligibility,
                'outstanding_principal' => $principal,
            ]);

            $this->audit->record('loan.submitted', $loan);

            return $loan;
        });
    }

    public function approve(Loan $loan, ?float $amount = null): Loan
    {
        return DB::transaction(function () use ($loan, $amount) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $approved = $amount ?? (float) $loan->principal;
            $loan->update([
                'status' => 'Approved',
                'approved_amount' => $approved,
                'outstanding_principal' => $approved,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->generateSchedule($loan->refresh());
            $this->audit->record('loan.approved', $loan, [], ['approved_amount' => $approved], $loan->loan_number);

            return $loan->refresh();
        });
    }

    public function disburse(Loan $loan, array $data = []): Loan
    {
        return DB::transaction(function () use ($loan, $data) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $loan->update([
                'status' => 'Active',
                'disbursement_date' => $data['disbursement_date'] ?? now()->toDateString(),
                'disbursed_by' => auth()->id(),
                'disbursed_at' => now(),
            ]);

            $this->audit->record('loan.disbursed', $loan, [], $data, $loan->loan_number);

            return $loan->refresh();
        });
    }

    public function eligibility(Member $member, LoanProduct $product, float $principal): array
    {
        $savings = (float) $member->savingsAccounts()->sum('current_balance');
        $contributions = (float) $member->contributions()->sum('amount_paid');
        $existingDebt = (float) $member->loans()->whereNotIn('status', ['Cleared', 'Rejected', 'Written Off'])->sum(DB::raw('outstanding_principal + outstanding_interest + outstanding_penalties'));
        $arrears = (float) $member->contributionSchedules()->whereIn('status', ['Partial', 'Overdue'])->sum('balance');
        $checks = [
            'active_member' => $member->status === 'Active',
            'minimum_amount' => ! $product->minimum_amount || $principal >= (float) $product->minimum_amount,
            'maximum_amount' => ! $product->maximum_amount || $principal <= (float) $product->maximum_amount,
            'existing_debt' => $existingDebt <= 0,
            'contribution_compliance' => $arrears <= 0,
        ];

        return [
            'eligible' => ! in_array(false, $checks, true),
            'checks' => $checks,
            'savings_balance' => $savings,
            'total_contributions' => $contributions,
            'existing_debt' => $existingDebt,
            'contribution_arrears' => $arrears,
            'explanation' => "Requested amount {$principal}; savings {$savings}; contributions {$contributions}; existing debt {$existingDebt}.",
        ];
    }

    public function generateSchedule(Loan $loan): int
    {
        return DB::transaction(function () use ($loan) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($loan->schedules()->exists()) {
                return 0;
            }

            $principal = (float) ($loan->approved_amount ?: $loan->principal);
            $term = max(1, (int) $loan->term_months);
            $monthlyPrincipal = round($principal / $term, 2);
            $remaining = $principal;
            $created = 0;

            for ($installment = 1; $installment <= $term; $installment++) {
                $principalComponent = $installment === $term ? $remaining : $monthlyPrincipal;
                $interest = $this->interestFor($loan, $remaining, $principal, $term);
                $total = round($principalComponent + $interest, 2);

                LoanSchedule::create([
                    'loan_id' => $loan->id,
                    'installment_number' => $installment,
                    'due_date' => now()->addMonthsNoOverflow($installment)->toDateString(),
                    'principal' => $principalComponent,
                    'interest' => $interest,
                    'penalty' => 0,
                    'total_due' => $total,
                    'amount_paid' => 0,
                    'balance' => $total,
                    'status' => 'Upcoming',
                ]);

                $remaining = max($remaining - $principalComponent, 0);
                $created++;
            }

            $loan->update(['outstanding_interest' => $loan->schedules()->sum('interest')]);

            return $created;
        });
    }

    public function recordRepayment(Loan $loan, array $data): LoanRepayment
    {
        return DB::transaction(function () use ($loan, $data) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $data['amount'];
            $remainingPayment = $amount;
            $allocation = ['penalty' => 0, 'interest' => 0, 'principal' => 0];

            foreach ($loan->schedules()->where('balance', '>', 0)->orderBy('due_date')->lockForUpdate()->get() as $schedule) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $paidToSchedule = min((float) $schedule->balance, $remainingPayment);
                $scheduleBalance = max((float) $schedule->balance - $paidToSchedule, 0);
                $schedule->update([
                    'amount_paid' => (float) $schedule->amount_paid + $paidToSchedule,
                    'balance' => $scheduleBalance,
                    'status' => $scheduleBalance <= 0 ? 'Paid' : 'Partial',
                ]);

                $penaltyPart = min((float) $schedule->penalty, $paidToSchedule);
                $interestPart = min((float) $schedule->interest, max($paidToSchedule - $penaltyPart, 0));
                $principalPart = max($paidToSchedule - $penaltyPart - $interestPart, 0);
                $allocation['penalty'] += $penaltyPart;
                $allocation['interest'] += $interestPart;
                $allocation['principal'] += $principalPart;
                $remainingPayment -= $paidToSchedule;
            }

            $repayment = LoanRepayment::create([
                'loan_id' => $loan->id,
                'loan_schedule_id' => $data['loan_schedule_id'] ?? null,
                'member_id' => $loan->member_id,
                'repayment_number' => $data['repayment_number'] ?? $this->numbers->loanRepaymentNumber(),
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'penalty_amount' => $allocation['penalty'],
                'interest_amount' => $allocation['interest'],
                'principal_amount' => $allocation['principal'],
                'payment_method' => $data['payment_method'] ?? 'Cash',
                'reference' => $data['reference'] ?? null,
                'status' => 'Posted',
                'allocation' => $allocation,
            ]);

            $loan->update([
                'total_paid' => (float) $loan->total_paid + $amount,
                'outstanding_principal' => max((float) $loan->outstanding_principal - $allocation['principal'], 0),
                'outstanding_interest' => max((float) $loan->outstanding_interest - $allocation['interest'], 0),
                'outstanding_penalties' => max((float) $loan->outstanding_penalties - $allocation['penalty'], 0),
                'status' => $loan->schedules()->where('balance', '>', 0)->exists() ? 'Active' : 'Cleared',
            ]);

            $this->audit->record('loan.repayment.recorded', $repayment);

            return $repayment;
        });
    }

    public function earlySettlement(Loan $loan): array
    {
        $loan->refresh();
        $principal = (float) $loan->outstanding_principal;
        $interest = (float) $loan->outstanding_interest;
        $penalty = (float) $loan->outstanding_penalties;

        return [
            'outstanding_principal' => $principal,
            'accrued_interest' => $interest,
            'penalty' => $penalty,
            'settlement_amount' => $principal + $interest + $penalty,
        ];
    }

    private function interestFor(Loan $loan, float $remaining, float $original, int $term): float
    {
        $rate = (float) $loan->interest_rate / 100;

        return match ($loan->interest_method) {
            'Interest-Free' => 0,
            'Fixed Charge' => round((float) $loan->interest_rate / $term, 2),
            'Reducing Balance' => round($remaining * $rate / $term, 2),
            default => round($original * $rate / $term, 2),
        };
    }
}
