<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\FinanceAccount;
use App\Models\FixedAsset;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\SupplierInvoice;
use App\Services\CostAccountingService;
use App\Services\FinanceDepartmentService;
use App\Services\FinanceService;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function __construct(
        private FinanceService $finance,
        private FinanceDepartmentService $department,
    ) {}

    public function index()
    {
        if (! $this->finance->ready()) {
            return redirect()->route('dashboard')->with('warning', 'Finance is enabled for this tenant, but the finance database tables are not installed yet. Run the finance migrations to open this module.');
        }

        $this->finance->seedAccounts();

        $reports = $this->finance->reports();
        $ar = Invoice::source()->where('balance', '>', 0)->with('client')->get();
        $ap = SupplierInvoice::whereRaw('total > amount_paid')->with('supplier')->get();
        $banks = BankAccount::with('ledgerAccount', 'transactions')->get();

        return view('finance.index', $reports + [
            'accounts' => FinanceAccount::with('parent')->orderBy('code')->get(),
            'journals' => JournalEntry::with('lines.account')->latest('entry_date')->limit(50)->get(),
            'banks' => $banks,
            'fixedAssets' => FixedAsset::with('schedules')->get(),
            'ar' => $ar,
            'ap' => $ap,
            'arAging' => $this->finance->aging($ar, 'due_date', 'balance'),
            'apAging' => $this->finance->aging($ap, 'due_date', 'outstanding'),
            'projects' => Project::orderBy('project_name')->get(),
            'costReport' => app(CostAccountingService::class)->report(now()->year),
            'periods' => DB::table('finance_periods')->where('business_id', ActiveBusiness::id())->latest('starts_at')->get(),
            'taxes' => DB::table('tax_records')->where('business_id', ActiveBusiness::id())->latest('period_end')->get(),
            'financeCockpit' => $this->department->cockpit($reports, $ar, $ap, $banks),
        ]);
    }

    public function storeAccount(Request $request)
    {
        FinanceAccount::create($this->accountData($request));

        return back()->with('status', 'Account created.');
    }

    public function updateAccount(Request $request, FinanceAccount $account)
    {
        $account->update($this->accountData($request, $account));

        return back()->with('status', 'Account updated.');
    }

    public function deactivateAccount(FinanceAccount $account)
    {
        $account->update(['is_active' => false]);

        return back()->with('status', 'Account deactivated. Existing ledger history remains intact.');
    }

    public function storeJournal(Request $request)
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'max:255'],
            'reason' => ['nullable', 'string'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'in:Monthly,Quarterly,Annually'],
            'account_id' => ['required', 'array', 'min:2'],
            'account_id.*' => ['required', Rule::exists('finance_accounts', 'id')->where('business_id', ActiveBusiness::id())],
            'debit' => ['required', 'array'],
            'debit.*' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['required', 'array'],
            'credit.*' => ['nullable', 'numeric', 'min:0'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $lines = [];
        foreach ($data['account_id'] as $index => $accountId) {
            $lines[] = [
                'finance_account_id' => $accountId,
                'project_id' => $data['project_id'] ?? null,
                'debit' => $data['debit'][$index] ?? 0,
                'credit' => $data['credit'][$index] ?? 0,
            ];
        }

        $this->finance->post([
            'entry_date' => $data['entry_date'],
            'description' => $data['description'],
            'reason' => $data['reason'] ?? null,
            'is_recurring' => $request->boolean('is_recurring'),
            'recurrence' => $data['recurrence'] ?? null,
            'next_run_at' => $request->boolean('is_recurring') ? now()->addMonth() : null,
        ], $lines);

        return back()->with('status', 'Balanced journal posted.');
    }

    public function reverse(Request $request, JournalEntry $journal)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->finance->reverse($journal, $data['reason']);

        return back()->with('status', 'Journal reversed with a new balancing entry.');
    }

    public function unreverse(Request $request, JournalEntry $journal)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->finance->unreverse($journal, $data['reason']);

        return back()->with('status', 'Journal restored and its reversal entry cancelled.');
    }

    public function sync()
    {
        $counts = $this->finance->syncLegacy();

        return back()->with('status', 'Legacy finance records synchronized: '.collect($counts)->map(fn ($value, $key) => "$key $value")->join(', ').'.');
    }

    public function storeBank(Request $request)
    {
        $data = $request->validate([
            'finance_account_id' => ['required', Rule::exists('finance_accounts', 'id')->where('business_id', ActiveBusiness::id())],
            'name' => ['required', 'max:255'],
            'type' => ['required', 'in:Bank,Cash,Petty Cash,Mobile Money,MPesa'],
            'institution' => ['nullable', 'max:255'],
            'account_number' => ['nullable', 'max:100'],
            'currency' => ['required', 'size:3'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        BankAccount::create($data);

        return back()->with('status', 'Cash or bank account created.');
    }

    public function storeBankTransaction(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('business_id', ActiveBusiness::id())],
            'contra_account_id' => ['required', Rule::exists('finance_accounts', 'id')->where('business_id', ActiveBusiness::id())],
            'transaction_date' => ['required', 'date'],
            'type' => ['required', 'in:Deposit,Withdrawal,Transfer In,Transfer Out'],
            'reference' => ['nullable', 'max:255'],
            'description' => ['required', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $bank = BankAccount::findOrFail($data['bank_account_id']);
        $incoming = in_array($data['type'], ['Deposit', 'Transfer In'], true);
        $journal = $this->finance->post(
            ['entry_date' => $data['transaction_date'], 'description' => $data['description']],
            [
                ['finance_account_id' => $bank->finance_account_id, 'debit' => $incoming ? $data['amount'] : 0, 'credit' => $incoming ? 0 : $data['amount']],
                ['finance_account_id' => $data['contra_account_id'], 'debit' => $incoming ? 0 : $data['amount'], 'credit' => $incoming ? $data['amount'] : 0],
            ]
        );

        BankTransaction::create($data + ['bank_account_id' => $bank->id, 'journal_entry_id' => $journal->id]);

        return back()->with('status', 'Bank transaction posted.');
    }

    public function reconcile(BankTransaction $transaction)
    {
        $transaction->update(['is_reconciled' => true, 'reconciled_at' => now(), 'reconciled_by' => auth()->id()]);

        return back()->with('status', 'Transaction reconciled.');
    }

    public function storeAsset(Request $request)
    {
        $data = $request->validate([
            'asset_account_id' => ['required', 'exists:finance_accounts,id'],
            'depreciation_account_id' => ['required', 'exists:finance_accounts,id'],
            'expense_account_id' => ['required', 'exists:finance_accounts,id'],
            'asset_number' => ['required', 'max:100'],
            'name' => ['required', 'max:255'],
            'category' => ['required', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'residual_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'depreciation_method' => ['required', 'in:Straight-line,Reducing balance'],
            'reducing_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'location' => ['nullable', 'max:255'],
            'assigned_to' => ['nullable', 'max:255'],
        ]);

        $asset = FixedAsset::create($data);
        $value = (float) $asset->cost;

        for ($month = 1; $month <= $asset->useful_life_months; $month++) {
            $depreciation = $asset->depreciation_method === 'Reducing balance'
                ? $value * ((float) $asset->reducing_rate / 100 / 12)
                : ((float) $asset->cost - (float) $asset->residual_value) / $asset->useful_life_months;
            $depreciation = min($depreciation, max($value - (float) $asset->residual_value, 0));

            $asset->schedules()->create([
                'period_date' => $asset->purchase_date->copy()->addMonths($month),
                'opening_value' => $value,
                'depreciation' => $depreciation,
                'closing_value' => $value - $depreciation,
            ]);

            $value -= $depreciation;
        }

        return back()->with('status', 'Asset and depreciation schedule created.');
    }

    public function closePeriod(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);

        DB::table('finance_periods')->insert($data + [
            'business_id' => ActiveBusiness::id(),
            'status' => 'Closed',
            'closed_by' => auth()->id(),
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Financial period locked.');
    }

    private function accountData(Request $request, ?FinanceAccount $account = null): array
    {
        $unique = Rule::unique('finance_accounts')->where('business_id', ActiveBusiness::id());
        if ($account) {
            $unique = $unique->ignore($account->id);
        }

        $data = $request->validate([
            'parent_id' => ['nullable', Rule::exists('finance_accounts', 'id')->where('business_id', ActiveBusiness::id())],
            'code' => ['required', 'max:30', $unique],
            'name' => ['required', 'max:255'],
            'type' => ['required', Rule::in(FinanceService::ACCOUNT_TYPES)],
            'subtype' => ['nullable', 'max:100'],
            'description' => ['nullable', 'string'],
            'tax_treatment' => ['nullable', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($account && isset($data['parent_id']) && (int) $data['parent_id'] === (int) $account->id) {
            throw \Illuminate\Validation\ValidationException::withMessages(['parent_id' => 'An account cannot be its own parent.']);
        }

        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        if ($account && ! $request->has('is_active')) {
            unset($data['is_active']);
        }

        return $data;
    }
}
