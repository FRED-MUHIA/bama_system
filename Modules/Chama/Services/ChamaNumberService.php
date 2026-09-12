<?php

namespace Modules\Chama\Services;

use App\Support\ActiveTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChamaNumberService
{
    public function memberNumber(): string { return $this->next('chama_members', 'member_number', 'MEM'); }
    public function ruleNumber(): string { return $this->next('chama_rules', 'rule_number', 'RULE'); }
    public function contributionTypeNumber(): string { return $this->next('chama_contribution_types', 'type_number', 'CT'); }
    public function scheduleNumber(): string { return $this->next('chama_contribution_schedules', 'schedule_number', 'CON'); }
    public function contributionNumber(): string { return $this->next('chama_contributions', 'contribution_number', 'CONPAY'); }
    public function savingsAccountNumber(): string { return $this->next('chama_savings_accounts', 'account_number', 'SAV'); }
    public function savingsTransactionNumber(): string { return $this->next('chama_savings_transactions', 'transaction_number', 'SAVTX'); }
    public function cycleNumber(): string { return $this->next('chama_merry_go_round_cycles', 'cycle_number', 'MGR'); }
    public function roundNumber(): string { return $this->next('chama_merry_go_round_rounds', 'round_number', 'MGRR'); }
    public function tableBankingSessionNumber(): string { return $this->next('chama_table_banking_sessions', 'session_number', 'TBS'); }
    public function loanProductNumber(): string { return $this->next('chama_loan_products', 'product_number', 'LP'); }
    public function loanNumber(): string { return $this->next('chama_loans', 'loan_number', 'LN'); }
    public function loanRepaymentNumber(): string { return $this->next('chama_loan_repayments', 'repayment_number', 'LNR'); }
    public function fineNumber(): string { return $this->next('chama_fines', 'fine_number', 'FINE'); }
    public function welfareNumber(): string { return $this->next('chama_welfare_requests', 'request_number', 'WEL'); }
    public function shareTransactionNumber(): string { return $this->next('chama_share_transactions', 'transaction_number', 'SHR'); }
    public function investmentNumber(): string { return $this->next('chama_investments', 'investment_number', 'INVEST'); }
    public function investmentIncomeNumber(): string { return $this->next('chama_investment_income', 'income_number', 'INCOME'); }
    public function dividendNumber(): string { return $this->next('chama_dividend_runs', 'dividend_number', 'DIV'); }
    public function meetingNumber(): string { return $this->next('chama_meetings', 'meeting_number', 'MTG'); }
    public function ballotNumber(): string { return $this->next('chama_ballots', 'ballot_number', 'VOTE'); }
    public function documentNumber(): string { return $this->next('chama_documents', 'document_number', 'DOC'); }
    public function allocationNumber(): string { return $this->next('chama_payment_allocations', 'allocation_number', 'ALLOC'); }
    public function cashHandoverNumber(): string { return $this->next('chama_cash_handovers', 'handover_number', 'CASH'); }
    public function memberExitNumber(): string { return $this->next('chama_member_exits', 'exit_number', 'EXIT'); }

    public function next(string $table, string $column, string $prefix): string
    {
        $tenantId = $this->tenantId();
        $year = now()->format('Y');
        $base = "{$prefix}-{$year}-";

        $this->lockTenant($tenantId);

        $highest = DB::table($table)
            ->where('tenant_id', $tenantId)
            ->where($column, 'like', $base.'%')
            ->lockForUpdate()
            ->pluck($column)
            ->reduce(function (int $highest, string $number) use ($base) {
                if (! preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $number, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return $base.str_pad((string) ($highest + 1), 5, '0', STR_PAD_LEFT);
    }

    private function tenantId(): int
    {
        $tenantId = ActiveTenant::id();

        if (! $tenantId) {
            throw ValidationException::withMessages(['tenant' => 'A tenant context is required for Chama numbering.']);
        }

        return (int) $tenantId;
    }

    private function lockTenant(int $tenantId): void
    {
        DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->first();
    }
}
