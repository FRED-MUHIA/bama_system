<?php

namespace App\Http\Controllers;

use App\Models\AccountingAllocation;
use App\Models\AccountingAuditLog;
use App\Models\AccountingBudget;
use App\Models\BudgetAlert;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Services\CostAccountingService;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CostAccountingController extends Controller
{
    public function __construct(private CostAccountingService $accounting) {}

    public function index(Request $request)
    {
        $year = (int) $request->integer('year', now()->year);
        return view('accounting.index', $this->accounting->report($year) + [
            'departments' => Department::with(['manager', 'costCenters.children'])->orderBy('name')->get(),
            'costCenters' => CostCenter::with('department')->orderBy('name')->get(),
            'projects' => Project::orderBy('project_name')->get(),
            'users' => User::where('is_active', true)->orderBy('name')->get(),
            'industries' => array_keys(CostAccountingService::INDUSTRIES),
            'auditLogs' => AccountingAuditLog::latest('created_at')->limit(30)->get(),
            'budgetAlerts' => BudgetAlert::with('budget')->whereNull('acknowledged_at')->latest()->get(),
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50', Rule::unique('departments')->where('business_id', ActiveBusiness::id())], 'manager_id' => ['nullable', 'exists:users,id'], 'description' => ['nullable', 'string']]);
        $department = Department::create($data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        $this->accounting->audit('created', $department);
        return back()->with('status', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $old = $department->getAttributes();
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50', Rule::unique('departments')->where('business_id', ActiveBusiness::id())->ignore($department)], 'manager_id' => ['nullable', 'exists:users,id'], 'description' => ['nullable', 'string']]);
        $department->update($data + ['updated_by' => auth()->id()]);
        $this->accounting->audit('updated', $department, $old);
        return back()->with('status', 'Department updated.');
    }

    public function archiveDepartment(Department $department)
    {
        $old = $department->getAttributes();
        $department->update(['is_active' => ! $department->is_active, 'updated_by' => auth()->id()]);
        $this->accounting->audit($department->is_active ? 'restored' : 'archived', $department, $old);
        return back()->with('status', 'Department status updated.');
    }

    public function storeCostCenter(Request $request)
    {
        $data = $request->validate(['department_id' => ['required', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())], 'parent_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id())], 'name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50', Rule::unique('cost_centers')->where('business_id', ActiveBusiness::id())], 'description' => ['nullable', 'string']]);
        if (! empty($data['parent_id']) && CostCenter::find($data['parent_id'])?->department_id != $data['department_id']) return back()->withErrors(['parent_id' => 'The parent must belong to the same department.'])->withInput();
        $center = CostCenter::create($data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        $this->accounting->audit('created', $center);
        return back()->with('status', 'Cost center created.');
    }

    public function archiveCostCenter(CostCenter $costCenter)
    {
        $old = $costCenter->getAttributes();
        $costCenter->update(['is_active' => ! $costCenter->is_active, 'updated_by' => auth()->id()]);
        $this->accounting->audit($costCenter->is_active ? 'restored' : 'archived', $costCenter, $old);
        return back()->with('status', 'Cost center status updated.');
    }

    public function updateCostCenter(Request $request, CostCenter $costCenter)
    {
        abort_if($costCenter->is_project && $request->filled('project_id'), 422, 'Project cost centers are managed by their project.');
        $old = $costCenter->getAttributes();
        $data = $request->validate(['department_id' => ['required', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())], 'parent_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id()), Rule::notIn([$costCenter->id])], 'name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50', Rule::unique('cost_centers')->where('business_id', ActiveBusiness::id())->ignore($costCenter)], 'description' => ['nullable', 'string']]);
        if (! empty($data['parent_id']) && CostCenter::find($data['parent_id'])?->department_id != $data['department_id']) return back()->withErrors(['parent_id' => 'The parent must belong to the same department.'])->withInput();
        $costCenter->update($data + ['updated_by' => auth()->id()]);
        $this->accounting->audit('updated', $costCenter, $old);
        return back()->with('status', 'Cost center updated.');
    }

    public function storeBudget(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2200'], 'amount' => ['required', 'numeric', 'min:0'], 'alert_threshold' => ['required', 'numeric', 'min:1', 'max:100'], 'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())], 'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id())], 'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())]]);
        $budget = AccountingBudget::create($data + ['status' => 'Draft', 'created_by' => auth()->id()]);
        $this->accounting->audit('created', $budget);
        return back()->with('status', 'Budget created.');
    }

    public function approveBudget(AccountingBudget $budget)
    {
        $old = $budget->getAttributes();
        $budget->update(['status' => 'Approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->accounting->audit('approved', $budget, $old);
        return back()->with('status', 'Budget approved.');
    }

    public function updateBudget(Request $request, AccountingBudget $budget)
    {
        abort_if($budget->status === 'Approved', 422, 'Approved budgets cannot be edited. Create a revised budget.');
        $old = $budget->getAttributes();
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2200'], 'amount' => ['required', 'numeric', 'min:0'], 'alert_threshold' => ['required', 'numeric', 'min:1', 'max:100']]);
        $budget->update($data);
        $this->accounting->audit('updated', $budget, $old);
        return back()->with('status', 'Budget updated.');
    }

    public function storeAllocation(Request $request)
    {
        $data = $request->validate(['transaction_type' => ['required', 'string', 'max:100'], 'transaction_id' => ['nullable', 'integer', 'min:1'], 'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', ActiveBusiness::id())], 'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('business_id', ActiveBusiness::id())], 'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())], 'direction' => ['required', Rule::in(['Revenue', 'Expense'])], 'category' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'transaction_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'min:0.01']]);
        if (empty($data['department_id']) && empty($data['cost_center_id']) && empty($data['project_id'])) return back()->withErrors(['department_id' => 'Tag at least a department, cost center, or project.'])->withInput();
        if (! empty($data['project_id'])) { $project = Project::find($data['project_id']); $center = $this->accounting->ensureProjectCostCenter($project); $data['cost_center_id'] ??= $center->id; $data['department_id'] ??= $center->department_id; }
        $allocation = AccountingAllocation::create($data + ['created_by' => auth()->id()]);
        $this->accounting->audit('allocated', $allocation);
        return back()->with('status', 'Transaction allocation saved.');
    }

    public function seedIndustry(Request $request)
    {
        $data = $request->validate(['industry' => ['required', Rule::in(array_keys(CostAccountingService::INDUSTRIES))]]);
        ActiveBusiness::current()?->update(['industry' => $data['industry']]);
        $this->accounting->seedIndustry($data['industry']);
        return back()->with('status', 'Industry departments created without replacing existing records.');
    }

    public function acknowledgeAlert(BudgetAlert $alert)
    {
        $alert->update(['acknowledged_at' => now(), 'acknowledged_by' => auth()->id()]);
        return back()->with('status', 'Budget alert acknowledged.');
    }

    public function projectReport(Request $request, string $type)
    {
        abort_unless(in_array($type, ['profit-loss', 'cash-flow', 'expenses', 'procurement', 'customer-payments']), 404);
        $project = Project::with(['client', 'invoices.payments', 'expenses', 'costs', 'purchaseOrders.supplier', 'supplierInvoices.supplier', 'receiptAllocations.receipt'])->findOrFail($request->integer('project_id'));
        $rows = match ($type) {
            'expenses' => $project->expenses->map(fn ($x) => [$x->expense_date?->toDateString(), $x->category, $x->description, $x->amount]),
            'procurement' => $project->supplierInvoices->map(fn ($x) => [$x->invoice_date?->toDateString(), $x->supplier?->name, $x->invoice_number, $x->total]),
            'customer-payments' => $project->invoices->flatMap(fn ($i) => $i->payments->map(fn ($x) => [$x->payment_date?->toDateString(), $i->invoice_number, $x->reference, $x->amount])),
            'cash-flow' => collect([['Revenue collected', $project->collected()], ['Cash expenses', $project->actualCost()], ['Net cash flow', $project->collected() - $project->actualCost()]]),
            default => collect([['Revenue', $project->revenue()], ['Expenses', $project->actualCost()], ['Net profit', $project->revenue() - $project->actualCost()], ['Margin %', $project->revenue() > 0 ? (($project->revenue() - $project->actualCost()) / $project->revenue()) * 100 : 0]]),
        };
        if ($request->boolean('csv')) {
            return response()->streamDownload(function () use ($rows) { $out = fopen('php://output', 'w'); foreach ($rows as $row) fputcsv($out, (array) $row); fclose($out); }, $project->project_name.'-'.$type.'.csv', ['Content-Type' => 'text/csv']);
        }
        return view('accounting.report', compact('project', 'type', 'rows'));
    }
}
