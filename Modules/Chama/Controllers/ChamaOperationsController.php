<?php

namespace Modules\Chama\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Chama\Models\Ballot;
use Modules\Chama\Models\ChamaDocument;
use Modules\Chama\Models\ChamaRule;
use Modules\Chama\Models\ContributionType;
use Modules\Chama\Models\Fine;
use Modules\Chama\Models\Investment;
use Modules\Chama\Models\Loan;
use Modules\Chama\Models\LoanProduct;
use Modules\Chama\Models\Meeting;
use Modules\Chama\Models\Member;
use Modules\Chama\Models\MerryGoRoundCycle;
use Modules\Chama\Models\SavingsAccount;
use Modules\Chama\Services\ChamaAuditService;
use Modules\Chama\Services\ChamaLoanService;
use Modules\Chama\Services\ChamaNumberService;
use Modules\Chama\Services\ChamaReportingService;
use Modules\Chama\Services\ContributionService;
use Modules\Chama\Services\DividendService;
use Modules\Chama\Services\InvestmentService;
use Modules\Chama\Services\MeetingService;
use Modules\Chama\Services\MemberService;
use Modules\Chama\Services\MerryGoRoundService;
use Modules\Chama\Services\SavingsService;
use Modules\Chama\Services\ShareCapitalService;
use Modules\Chama\Services\TableBankingService;
use Modules\Chama\Services\VotingService;
use Modules\Chama\Services\WelfareService;

class ChamaOperationsController extends Controller
{
    public function store(
        Request $request,
        string $type,
        MemberService $members,
        ContributionService $contributions,
        SavingsService $savings,
        MerryGoRoundService $merryGoRound,
        TableBankingService $tableBanking,
        ChamaLoanService $loans,
        WelfareService $welfare,
        ShareCapitalService $shares,
        InvestmentService $investments,
        DividendService $dividends,
        MeetingService $meetings,
        VotingService $voting,
        ChamaNumberService $numbers,
        ChamaAuditService $audit,
    ) {
        match ($type) {
            'members' => $members->create($this->memberData($request)),
            'contribution-types' => $contributions->createType($this->contributionTypeData($request)),
            'contribution-schedules' => $this->generateSchedules($request, $contributions),
            'contributions' => $contributions->recordPayment($this->contributionData($request)),
            'savings-accounts' => $savings->openAccount($this->savingsAccountData($request)),
            'savings-transactions' => $savings->recordTransaction(
                SavingsAccount::findOrFail($request->validate(['savings_account_id' => ['required', $this->tenantExistsRule('chama_savings_accounts')]])['savings_account_id']),
                $this->savingsTransactionData($request)
            ),
            'merry-go-round-cycles' => $merryGoRound->createCycle($this->merryGoRoundCycleData($request)),
            'merry-go-round-rounds' => $this->storeMerryGoRoundRound($request, $merryGoRound),
            'table-banking-sessions' => $tableBanking->recordSession($this->tableBankingData($request)),
            'loan-products' => $loans->createProduct($this->loanProductData($request)),
            'loans' => $loans->apply($this->loanData($request)),
            'loan-repayments' => $this->storeLoanRepayment($request, $loans),
            'fines' => $this->storeFine($request, $numbers, $audit),
            'welfare-requests' => $welfare->request($this->welfareData($request)),
            'shares' => $shares->record($this->shareData($request)),
            'investments' => $investments->create($this->investmentData($request)),
            'investment-income' => $this->storeInvestmentIncome($request, $investments),
            'dividends' => $dividends->createRun($this->dividendData($request)),
            'meetings' => $meetings->create($this->meetingData($request)),
            'attendance' => $this->storeAttendance($request, $meetings),
            'ballots' => $voting->createBallot($this->ballotData($request)),
            'votes' => $this->storeVote($request, $voting),
            'rules' => $this->storeRules($request, $numbers, $audit),
            'documents' => $this->storeDocument($request, $numbers, $audit),
            default => abort(404),
        };

        return back()->with('status', str($type)->headline().' saved.');
    }

    public function approveMember(Member $member, MemberService $members)
    {
        $members->approve($member);

        return back()->with('status', 'Member approved.');
    }

    public function approveLoan(Loan $loan, Request $request, ChamaLoanService $loans)
    {
        $data = $request->validate(['approved_amount' => ['nullable', 'numeric', 'min:0']]);
        $loans->approve($loan, isset($data['approved_amount']) ? (float) $data['approved_amount'] : null);

        return back()->with('status', 'Loan approved and repayment schedule generated.');
    }

    public function disburseLoan(Loan $loan, ChamaLoanService $loans)
    {
        $loans->disburse($loan);

        return back()->with('status', 'Loan marked as disbursed.');
    }

    public function report(string $type, ChamaReportingService $reports)
    {
        abort_unless(in_array($type, ['members', 'contributions', 'arrears', 'savings', 'loans', 'fines', 'table-banking'], true), 404);

        return $reports->csv($type);
    }

    private function memberData(Request $request): array
    {
        $data = $this->clean($request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'national_id' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:180'],
            'gender' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'next_of_kin_name' => ['nullable', 'string', 'max:160'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:40'],
            'join_date' => ['nullable', 'date'],
            'membership_type' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(Member::STATUSES)],
            'occupation' => ['nullable', 'string', 'max:120'],
            'employer' => ['nullable', 'string', 'max:160'],
            'registration_fee_due' => ['nullable', 'numeric', 'min:0'],
            'registration_fee_paid' => ['nullable', 'numeric', 'min:0'],
            'share_capital_required' => ['nullable', 'numeric', 'min:0'],
            'initial_contribution_required' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        $data['next_of_kin'] = array_filter([
            'name' => $data['next_of_kin_name'] ?? null,
            'phone' => $data['next_of_kin_phone'] ?? null,
        ]);
        unset($data['next_of_kin_name'], $data['next_of_kin_phone']);

        return $data;
    }

    private function contributionTypeData(Request $request): array
    {
        return $this->clean($request->validate([
            'name' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', Rule::in(['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Annual', 'One-Time'])],
            'is_mandatory' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'grace_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'late_penalty' => ['nullable', 'numeric', 'min:0'],
            'fund_bucket' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]));
    }

    private function generateSchedules(Request $request, ContributionService $contributions): void
    {
        $data = $this->clean($request->validate([
            'contribution_type_id' => ['required', $this->tenantExistsRule('chama_contribution_types')],
            'start_date' => ['nullable', 'date'],
            'periods' => ['required', 'integer', 'min:1', 'max:60'],
        ]));

        $contributions->generateSchedule(
            ContributionType::findOrFail($data['contribution_type_id']),
            ! empty($data['start_date']) ? now()->parse($data['start_date']) : null,
            (int) $data['periods']
        );
    }

    private function contributionData(Request $request): array
    {
        return $this->clean($request->validate([
            'member_id' => ['required_without:contribution_schedule_id', $this->tenantExistsRule('chama_members')],
            'contribution_type_id' => ['nullable', $this->tenantExistsRule('chama_contribution_types')],
            'contribution_schedule_id' => ['nullable', $this->tenantExistsRule('chama_contribution_schedules')],
            'period' => ['nullable', 'string', 'max:80'],
            'amount_due' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(['M-PESA', 'Cash', 'Bank Transfer', 'Card', 'Other'])],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]));
    }

    private function savingsAccountData(Request $request): array
    {
        return $this->clean($request->validate([
            'member_id' => ['required', $this->tenantExistsRule('chama_members')],
            'account_type' => ['required', Rule::in(['Regular Savings', 'Voluntary Savings', 'Fixed Savings', 'Emergency Savings', 'Goal-Based Savings'])],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));
    }

    private function savingsTransactionData(Request $request): array
    {
        return $this->clean($request->validate([
            'transaction_type' => ['required', Rule::in(['Deposit', 'Withdrawal', 'Transfer In', 'Transfer Out', 'Interest'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::in(['M-PESA', 'Cash', 'Bank Transfer', 'Card', 'Other'])],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]));
    }

    private function merryGoRoundCycleData(Request $request): array
    {
        return $this->clean($request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contribution_amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', Rule::in(['Weekly', 'Monthly', 'Custom'])],
            'start_date' => ['nullable', 'date'],
            'next_payout_date' => ['nullable', 'date'],
            'payout_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['Planned', 'Active', 'Completed', 'Suspended'])],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => [$this->tenantExistsRule('chama_members')],
        ]));
    }

    private function storeMerryGoRoundRound(Request $request, MerryGoRoundService $service): void
    {
        $data = $this->clean($request->validate([
            'cycle_id' => ['required', $this->tenantExistsRule('chama_merry_go_round_cycles')],
            'beneficiary_id' => ['required', $this->tenantExistsRule('chama_members')],
            'due_date' => ['nullable', 'date'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_collected' => ['nullable', 'numeric', 'min:0'],
            'payout_amount' => ['nullable', 'numeric', 'min:0'],
            'payout_method' => ['nullable', 'string', 'max:80'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'payout_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));

        $cycle = MerryGoRoundCycle::findOrFail($data['cycle_id']);
        $service->createRound($cycle, $data);
    }

    private function tableBankingData(Request $request): array
    {
        return $this->clean($request->validate([
            'meeting_id' => ['nullable', $this->tenantExistsRule('chama_meetings')],
            'session_date' => ['required', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'contributions' => ['nullable', 'numeric'],
            'savings' => ['nullable', 'numeric'],
            'loan_repayments' => ['nullable', 'numeric'],
            'interest' => ['nullable', 'numeric'],
            'fines' => ['nullable', 'numeric'],
            'expenses' => ['nullable', 'numeric'],
            'loans_disbursed' => ['nullable', 'numeric'],
            'closing_balance' => ['required', 'numeric'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));
    }

    private function loanProductData(Request $request): array
    {
        return $this->clean($request->validate([
            'name' => ['required', 'string', 'max:160'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'interest_method' => ['required', Rule::in(['Flat Rate', 'Reducing Balance', 'Fixed Charge', 'Interest-Free'])],
            'term_months' => ['required', 'integer', 'min:1', 'max:240'],
            'minimum_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_amount' => ['nullable', 'numeric', 'min:0'],
            'guarantor_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'loan_limit_formula' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]));
    }

    private function loanData(Request $request): array
    {
        return $this->clean($request->validate([
            'loan_product_id' => ['required', $this->tenantExistsRule('chama_loan_products')],
            'member_id' => ['required', $this->tenantExistsRule('chama_members')],
            'principal' => ['required', 'numeric', 'min:1'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'interest_method' => ['nullable', Rule::in(['Flat Rate', 'Reducing Balance', 'Fixed Charge', 'Interest-Free'])],
            'term_months' => ['nullable', 'integer', 'min:1', 'max:240'],
            'application_date' => ['nullable', 'date'],
            'purpose' => ['nullable', 'string', 'max:1000'],
        ]));
    }

    private function storeLoanRepayment(Request $request, ChamaLoanService $service): void
    {
        $data = $this->clean($request->validate([
            'loan_id' => ['required', $this->tenantExistsRule('chama_loans')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(['M-PESA', 'Cash', 'Bank Transfer', 'Card', 'Savings Offset', 'Other'])],
            'reference' => ['nullable', 'string', 'max:120'],
        ]));

        $service->recordRepayment(Loan::findOrFail($data['loan_id']), $data);
    }

    private function storeFine(Request $request, ChamaNumberService $numbers, ChamaAuditService $audit): void
    {
        $data = $this->clean($request->validate([
            'member_id' => ['required', $this->tenantExistsRule('chama_members')],
            'fine_type' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'fine_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['Pending', 'Paid', 'Waived', 'Appealed'])],
        ]));

        $fine = Fine::create(array_merge($data, ['fine_number' => $numbers->fineNumber()]));
        $audit->record('fine.created', $fine);
    }

    private function welfareData(Request $request): array
    {
        return $this->clean($request->validate([
            'member_id' => ['required', $this->tenantExistsRule('chama_members')],
            'request_type' => ['required', 'string', 'max:120'],
            'amount_requested' => ['required', 'numeric', 'min:0'],
            'amount_approved' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));
    }

    private function shareData(Request $request): array
    {
        return $this->clean($request->validate([
            'member_id' => ['required', $this->tenantExistsRule('chama_members')],
            'transaction_type' => ['required', Rule::in(['Purchase', 'Transfer In', 'Transfer Out', 'Withdrawal'])],
            'share_units' => ['required', 'numeric', 'min:0'],
            'price_per_share' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]));
    }

    private function investmentData(Request $request): array
    {
        return $this->clean($request->validate([
            'name' => ['required', 'string', 'max:180'],
            'investment_type' => ['required', 'string', 'max:120'],
            'purchase_date' => ['nullable', 'date'],
            'initial_cost' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'allocation_basis' => ['nullable', Rule::in(['Equal Shares', 'Share Capital Ratio', 'Contribution Ratio', 'Custom Allocation'])],
            'status' => ['nullable', 'string', 'max:40'],
        ]));
    }

    private function storeInvestmentIncome(Request $request, InvestmentService $service): void
    {
        $data = $this->clean($request->validate([
            'investment_id' => ['required', $this->tenantExistsRule('chama_investments')],
            'income_type' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0'],
            'income_date' => ['nullable', 'date'],
            'allocation_status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]));

        $investment = Investment::findOrFail($data['investment_id']);
        unset($data['investment_id']);
        $service->recordIncome($investment, $data);
    }

    private function dividendData(Request $request): array
    {
        return $this->clean($request->validate([
            'period' => ['required', 'string', 'max:80'],
            'distribution_basis' => ['required', Rule::in(['Share Capital', 'Savings', 'Contributions', 'Investment Units', 'Custom Formula'])],
            'profit_available' => ['required', 'numeric', 'min:0'],
            'reserve_allocation' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));
    }

    private function meetingData(Request $request): array
    {
        return $this->clean($request->validate([
            'meeting_type' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:180'],
            'online_link' => ['nullable', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:3000'],
            'chairperson_id' => ['nullable', $this->tenantExistsRule('chama_members')],
            'secretary_id' => ['nullable', $this->tenantExistsRule('chama_members')],
            'quorum_required_count' => ['nullable', 'integer', 'min:0'],
            'quorum_required_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));
    }

    private function storeAttendance(Request $request, MeetingService $service): void
    {
        $data = $this->clean($request->validate([
            'meeting_id' => ['required', $this->tenantExistsRule('chama_meetings')],
            'member_id' => ['required', $this->tenantExistsRule('chama_members')],
            'status' => ['required', Rule::in(['Present', 'Absent', 'Late', 'Excused'])],
            'fine_amount' => ['nullable', 'numeric', 'min:0'],
        ]));

        $service->markAttendance(Meeting::findOrFail($data['meeting_id']), Member::findOrFail($data['member_id']), $data['status'], (float) ($data['fine_amount'] ?? 0));
    }

    private function ballotData(Request $request): array
    {
        $data = $this->clean($request->validate([
            'meeting_id' => ['nullable', $this->tenantExistsRule('chama_meetings')],
            'title' => ['required', 'string', 'max:180'],
            'vote_type' => ['required', Rule::in(['Yes / No', 'Multiple Choice', 'Candidate Election'])],
            'is_secret' => ['nullable', 'boolean'],
            'options_text' => ['nullable', 'string', 'max:1000'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));

        $data['options'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['options_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->values()
            ->all();
        unset($data['options_text']);

        return $data;
    }

    private function storeVote(Request $request, VotingService $service): void
    {
        $data = $this->clean($request->validate([
            'ballot_id' => ['required', $this->tenantExistsRule('chama_ballots')],
            'member_id' => ['required', $this->tenantExistsRule('chama_members')],
            'candidate_member_id' => ['nullable', $this->tenantExistsRule('chama_members')],
            'vote_value' => ['nullable', 'string', 'max:180'],
        ]));

        $service->vote(Ballot::findOrFail($data['ballot_id']), Member::findOrFail($data['member_id']), $data);
    }

    private function storeRules(Request $request, ChamaNumberService $numbers, ChamaAuditService $audit): void
    {
        $data = $this->clean($request->validate([
            'name' => ['required', 'string', 'max:160'],
            'effective_from' => ['nullable', 'date'],
            'contribution_amount' => ['nullable', 'numeric', 'min:0'],
            'meeting_frequency' => ['nullable', 'string', 'max:80'],
            'late_penalty' => ['nullable', 'numeric', 'min:0'],
            'loan_limit' => ['nullable', 'string', 'max:120'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'guarantor_count' => ['nullable', 'integer', 'min:0'],
            'dividend_formula' => ['nullable', 'string', 'max:180'],
            'voting_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'quorum' => ['nullable', 'string', 'max:120'],
            'withdrawal_rules' => ['nullable', 'string', 'max:1000'],
        ]));

        $rule = ChamaRule::create([
            'rule_number' => $numbers->ruleNumber(),
            'name' => $data['name'],
            'rules' => collect($data)->except(['name', 'effective_from'])->filter()->all(),
            'effective_from' => $data['effective_from'] ?? now()->toDateString(),
            'status' => 'Draft',
            'created_by' => auth()->id(),
        ]);

        $audit->record('rules.created', $rule);
    }

    private function storeDocument(Request $request, ChamaNumberService $numbers, ChamaAuditService $audit): void
    {
        $data = $this->clean($request->validate([
            'member_id' => ['nullable', $this->tenantExistsRule('chama_members')],
            'document_template_id' => ['nullable', 'exists:document_templates,id'],
            'document_type' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'file' => ['nullable', 'file', 'max:10240'],
            'status' => ['nullable', 'string', 'max:40'],
        ]));

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('chama/documents', 'public');
        }

        unset($data['file']);
        $document = ChamaDocument::create(array_merge($data, [
            'document_number' => $numbers->documentNumber(),
            'status' => $data['status'] ?? 'Active',
        ]));

        $audit->record('document.created', $document);
    }

    private function clean(array $data): array
    {
        return collect($data)->reject(fn ($value) => $value === null)->all();
    }

    private function tenantExistsRule(string $table)
    {
        return Rule::exists($table, 'id')->where(fn ($query) => $query->where('tenant_id', ActiveTenant::id()));
    }
}
