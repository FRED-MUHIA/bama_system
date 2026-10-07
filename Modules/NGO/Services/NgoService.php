<?php

namespace Modules\NGO\Services;

use App\Models\Project;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\DB;
use Modules\NGO\Models\NgoActivity;
use Modules\NGO\Models\NgoBeneficiary;
use Modules\NGO\Models\NgoDonor;
use Modules\NGO\Models\NgoProgram;

class NgoService
{
    public function dashboard(): array
    {
        $businessId = ActiveBusiness::id();
        $activePrograms = NgoProgram::where('status', 'Active')->count();
        $activeBeneficiaries = NgoBeneficiary::where('status', 'Active')->count();
        $activeDonors = NgoDonor::where('status', 'Active')->count();
        $activeProjects = Project::count();
        $activitiesDue = NgoActivity::whereIn('status', ['Planned', 'In Progress'])->whereDate('ends_on', '<=', today())->count();
        $activitiesPending = NgoActivity::whereIn('status', ['Planned', 'In Progress'])->count();
        $activitiesCompleted = NgoActivity::where('status', 'Completed')->count();
        $income = $expenses = $cash = $bank = 0.0;

        if ($businessId && DB::getSchemaBuilder()->hasTable('journal_entries') && DB::getSchemaBuilder()->hasTable('journal_lines')) {
            $accountTotals = DB::table('journal_lines')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->join('finance_accounts', 'finance_accounts.id', '=', 'journal_lines.finance_account_id')
                ->where('journal_entries.business_id', $businessId)
                ->where('journal_entries.status', 'Posted')
                ->select('finance_accounts.type', 'finance_accounts.subtype', DB::raw('SUM(journal_lines.debit) as debits'), DB::raw('SUM(journal_lines.credit) as credits'))
                ->groupBy('finance_accounts.type', 'finance_accounts.subtype')->get();
            $income = (float) $accountTotals->whereIn('type', ['Revenue', 'Other Income'])->sum(fn ($row) => $row->credits - $row->debits);
            $expenses = (float) $accountTotals->whereIn('type', ['Cost of Sales', 'Expense', 'Other Expense'])->sum(fn ($row) => $row->debits - $row->credits);
            $cash = (float) $accountTotals->filter(fn ($row) => in_array(strtolower((string) $row->subtype), ['cash', 'bank', 'mobile money'], true))->sum(fn ($row) => $row->debits - $row->credits);
            $bank = (float) $accountTotals->filter(fn ($row) => strtolower((string) $row->subtype) === 'bank')->sum(fn ($row) => $row->debits - $row->credits);
        }

        return [
            'Active Programs' => $activePrograms,
            'Active Projects' => $activeProjects,
            'Active Beneficiaries' => $activeBeneficiaries,
            'Active Donors' => $activeDonors,
            'Total Income' => $income,
            'Total Expenses' => $expenses,
            'Funds Remaining' => $income - $expenses,
            'Current Cash / M-PESA Balance' => $cash,
            'Bank Balance' => $bank,
            'Activities Due' => $activitiesDue,
            'Activities Pending' => $activitiesPending,
            'Activities Completed' => $activitiesCompleted,
        ];
    }

    public function programPerformance()
    {
        return NgoProgram::with('sector')
            ->withCount('activities')
            ->orderByRaw("CASE status WHEN 'Active' THEN 0 WHEN 'Planning' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->limit(8)->get();
    }
}
