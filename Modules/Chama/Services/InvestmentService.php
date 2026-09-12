<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chama\Models\Investment;
use Modules\Chama\Models\InvestmentIncome;

class InvestmentService
{
    public function __construct(private ChamaNumberService $numbers, private ChamaAuditService $audit) {}

    public function create(array $data): Investment
    {
        return DB::transaction(function () use ($data) {
            $investment = Investment::create(array_merge($data, [
                'investment_number' => $data['investment_number'] ?? $this->numbers->investmentNumber(),
                'current_value' => $data['current_value'] ?? ($data['initial_cost'] ?? 0),
                'status' => $data['status'] ?? 'Active',
            ]));

            $this->audit->record('investment.created', $investment);

            return $investment;
        });
    }

    public function recordIncome(Investment $investment, array $data): InvestmentIncome
    {
        return DB::transaction(function () use ($investment, $data) {
            $income = InvestmentIncome::create(array_merge($data, [
                'investment_id' => $investment->id,
                'income_number' => $data['income_number'] ?? $this->numbers->investmentIncomeNumber(),
                'income_date' => $data['income_date'] ?? now()->toDateString(),
            ]));

            $investment->increment('income', (float) $income->amount);
            $this->audit->record('investment.income.recorded', $income);

            return $income;
        });
    }
}
