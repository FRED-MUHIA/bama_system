<?php

namespace App\Services;

use App\Models\AccountingAllocation;
use App\Models\AccountingAuditLog;
use App\Models\AccountingBudget;
use App\Models\BudgetAlert;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Project;
use App\Support\ActiveBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CostAccountingService
{
    public const INDUSTRIES = [
        'Technology' => ['ICT Services', 'Support', 'Projects', 'Procurement', 'Sales'],
        'Construction' => ['Projects', 'Site Operations', 'Equipment', 'Procurement', 'Safety'],
        'Retail' => ['Sales', 'Inventory', 'Procurement', 'Marketing'],
        'Healthcare' => ['Medical Services', 'Laboratory', 'Pharmacy', 'Administration'],
        'Manufacturing' => ['Production', 'Maintenance', 'Procurement', 'Quality Control', 'Logistics'],
    ];

    public function seedIndustry(string $industry): void
    {
        foreach (self::INDUSTRIES[$industry] ?? [] as $name) {
            $department = Department::firstOrCreate(
                ['code' => strtoupper(Str::substr(Str::slug($name, ''), 0, 12))],
                ['name' => $name, 'is_active' => true, 'created_by' => auth()->id()]
            );
            if ($name === 'Projects') {
                Project::whereNull('cost_center_id')->each(fn (Project $project) => $this->ensureProjectCostCenter($project, $department));
            }
        }
    }

    public function ensureProjectCostCenter(Project $project, ?Department $department = null): CostCenter
    {
        return DB::transaction(function () use ($project, $department) {
            $department ??= Department::firstOrCreate(
                ['code' => 'PROJECTS'],
                ['name' => 'Projects', 'is_active' => true, 'created_by' => auth()->id()]
            );
            $center = CostCenter::firstOrCreate(
                ['project_id' => $project->id],
                ['department_id' => $department->id, 'name' => $project->project_name, 'code' => 'PRJ-'.$project->id, 'is_project' => true, 'created_by' => auth()->id()]
            );
            if ($project->cost_center_id !== $center->id) {
                $project->forceFill(['cost_center_id' => $center->id])->saveQuietly();
            }
            return $center;
        });
    }

    public function report(int $year): array
    {
        $projects = Project::with([
            'costCenter.department',
            'receiptAllocations',
            'costs',
            'expenses' => fn ($query) => $query->whereYear('expense_date', $year),
            'supplierInvoices' => fn ($query) => $query->whereYear('invoice_date', $year),
        ])->get();
        $businessId = ActiveBusiness::id();
        $projectInvoices = Invoice::withoutGlobalScopes()
            ->whereIn('project_id', $projects->pluck('id'))
            ->when($businessId, fn ($query) => $query->where('business_id', $businessId))
            ->whereYear('invoice_date', $year)
            ->get()
            ->groupBy('project_id');

        $projects->each(fn (Project $project) => $project->setRelation('invoices', $projectInvoices->get($project->id, collect())));

        $allocations = AccountingAllocation::with('department', 'costCenter', 'project')->whereYear('transaction_date', $year)->get();
        $projectRows = $projects->map(function (Project $project) {
            $revenue = $project->revenue();
            $expenses = $project->actualCost();
            $collected = $project->collected();
            $payables = $project->supplierInvoices->sum(fn ($invoice) => max((float) $invoice->total - (float) $invoice->amount_paid, 0));
            return ['project' => $project, 'department' => $project->costCenter?->department, 'cost_center' => $project->costCenter, 'revenue' => $revenue, 'expenses' => $expenses, 'profit' => $revenue - $expenses, 'margin' => $revenue > 0 ? (($revenue - $expenses) / $revenue) * 100 : 0, 'collected' => $collected, 'receivables' => max($revenue - $collected, 0), 'payables' => $payables, 'cash_flow' => $collected - $expenses];
        });
        $departmentRows = Department::with('costCenters')->get()->map(function (Department $department) use ($projectRows, $allocations) {
            $projects = $projectRows->where('department.id', $department->id);
            $manual = $allocations->where('department_id', $department->id);
            $revenue = $projects->sum('revenue') + $manual->where('direction', 'Revenue')->sum('amount');
            $expenses = $projects->sum('expenses') + $manual->where('direction', 'Expense')->sum('amount');
            return ['department' => $department, 'revenue' => $revenue, 'expenses' => $expenses, 'profit' => $revenue - $expenses, 'cash_flow' => $projects->sum('cash_flow'), 'receivables' => $projects->sum('receivables'), 'payables' => $projects->sum('payables')];
        });
        $costCenterRows = CostCenter::with('department')->get()->map(function (CostCenter $center) use ($projectRows, $allocations) {
            $projects = $projectRows->where('cost_center.id', $center->id);
            $manual = $allocations->where('cost_center_id', $center->id);
            $revenue = $projects->sum('revenue') + $manual->where('direction', 'Revenue')->sum('amount');
            $expenses = $projects->sum('expenses') + $manual->where('direction', 'Expense')->sum('amount');
            return ['cost_center' => $center, 'revenue' => $revenue, 'expenses' => $expenses, 'profit' => $revenue - $expenses];
        });
        $budgets = AccountingBudget::with('department', 'costCenter', 'project')->where('fiscal_year', $year)->get()->map(function (AccountingBudget $budget) use ($projectRows, $departmentRows, $allocations) {
            $actual = $budget->project_id ? ($projectRows->firstWhere('project.id', $budget->project_id)['expenses'] ?? 0)
                : ($budget->cost_center_id ? $allocations->where('cost_center_id', $budget->cost_center_id)->where('direction', 'Expense')->sum('amount')
                : ($budget->department_id ? ($departmentRows->firstWhere('department.id', $budget->department_id)['expenses'] ?? 0) : $departmentRows->sum('expenses')));
            return ['budget' => $budget, 'actual' => $actual, 'variance' => (float) $budget->amount - $actual, 'utilization' => $budget->amount > 0 ? ($actual / (float) $budget->amount) * 100 : 0];
        });
        foreach ($budgets as $row) {
            if ($row['utilization'] >= (float) $row['budget']->alert_threshold) {
                $severity = $row['utilization'] >= 100 ? 'Overspent' : 'Threshold';
                BudgetAlert::updateOrCreate(['accounting_budget_id' => $row['budget']->id, 'severity' => $severity], ['actual_amount' => $row['actual'], 'utilization' => $row['utilization'], 'message' => $row['budget']->name.' is at '.number_format($row['utilization'], 1).'% utilization.']);
            }
        }
        return compact('projectRows', 'departmentRows', 'costCenterRows', 'budgets', 'allocations', 'year');
    }

    public function audit(string $event, Model $model, array $old = []): void
    {
        AccountingAuditLog::create(['user_id' => auth()->id(), 'event' => $event, 'auditable_type' => $model::class, 'auditable_id' => $model->getKey(), 'old_values' => $old ?: null, 'new_values' => $model->getAttributes(), 'created_at' => now()]);
    }
}
