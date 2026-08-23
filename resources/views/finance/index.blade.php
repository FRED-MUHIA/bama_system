@extends('layouts.app')
@section('title','Finance')

@section('content')
@php
    $money = fn($value) => 'KES '.number_format((float) $value, 2);
    $scorecards = $financeCockpit['scorecards'] ?? [];
    $canOpenEtims = Route::has('etims.dashboard')
        && \App\Support\SchemaCache::hasTable('etims_submissions')
        && auth()->user()?->hasPermission('etims.view');
@endphp

<style>
    .finance-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:24px}
    .finance-kicker{font-size:.76rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#666}
    .finance-title{font-size:1.35rem;font-weight:900;margin:0;color:#050806}
    .finance-muted{color:#667085}
    .finance-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .finance-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.05)}
    .finance-metric{border:1px solid #edf0f4;border-radius:10px;padding:12px;background:#fbfffc}
    .finance-metric strong{display:block;font-size:1.12rem}
    .finance-row{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #edf0f4;padding:10px 0}
    .finance-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .62rem;background:#e9fff2;color:#008342;font-weight:800;font-size:.78rem}
    .finance-risk{border-left:4px solid #00A651;padding:10px 12px;background:#fbfffc;border-radius:8px}
    .finance-risk.warn{border-color:#f4b400;background:#fffaf0}
    .finance-risk.danger{border-color:#dc3545;background:#fff5f5}
    @media(max-width:1100px){.finance-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:700px){.finance-grid{grid-template-columns:1fr}.finance-row{display:grid}}
</style>

<div class="finance-head">
    <div>
        <div class="finance-kicker">{{ $financeCockpit['industry'] ?? 'Shared Finance' }}</div>
        <h2 class="finance-title">Finance & General Ledger</h2>
        <p class="finance-muted mb-0">Double-entry accounting, receivables, payables, cash, assets, tax, controls, and industry profitability.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($canOpenEtims)
            <a class="btn btn-outline-dark" href="{{ route('etims.dashboard') }}"><i class="bi bi-receipt-cutoff"></i> Tax & ETIMS</a>
        @endif
        <form method="post" action="{{ route('finance.sync') }}">
            @csrf
            <button class="btn btn-dark"><i class="bi bi-arrow-repeat"></i> Sync existing transactions</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Revenue', $income, 'success'],
        ['Expenses', $expenses, 'danger'],
        ['Net Profit', $income - $expenses, ($income - $expenses >= 0 ? 'success' : 'danger')],
        ['Assets', $assets, 'primary'],
        ['Liabilities', $liabilities, 'warning'],
        ['Outstanding AR', $ar->sum('balance'), 'info'],
    ] as [$label, $value, $color])
        <div class="col-md-4 col-xl-2">
            <div class="finance-card h-100">
                <span class="finance-muted small">{{ $label }}</span>
                <strong class="fs-6 text-{{ $color }}">{{ $money($value) }}</strong>
            </div>
        </div>
    @endforeach
</div>

<div class="finance-grid mb-4">
    @foreach($scorecards as $label => $value)
        <div class="finance-metric">
            <div class="finance-kicker">{{ $label }}</div>
            <strong class="{{ $value < 0 ? 'text-danger' : 'text-success' }}">
                {{ str_contains($label, 'Margin') ? number_format((float) $value, 2).'%' : $money($value) }}
            </strong>
        </div>
    @endforeach
</div>

<ul class="nav nav-tabs mb-3">
    @foreach([
        'dash' => 'Dashboard',
        'industry' => 'Industry Finance',
        'gl' => 'General Ledger',
        'coa' => 'Chart of Accounts',
        'ar' => 'AR / AP',
        'cash' => 'Cash & Bank',
        'assets' => 'Fixed Assets',
        'reports' => 'Reports',
        'controls' => 'Controls',
    ] as $id => $label)
        <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#f-{{ $id }}">{{ $label }}</button></li>
    @endforeach
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="f-dash">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Receivable aging</h3>
                    @foreach($arAging as $label => $value)
                        <div class="finance-row"><span>{{ $label }}</span><strong>{{ $money($value) }}</strong></div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Payable aging</h3>
                    @foreach($apAging as $label => $value)
                        <div class="finance-row"><span>{{ $label }}</span><strong>{{ $money($value) }}</strong></div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-7">
                <div class="finance-card">
                    <h3 class="h6">Cash movement this month</h3>
                    @foreach($financeCockpit['cash_movement'] as $label => $value)
                        <div class="finance-row"><span>{{ $label }}</span><strong>{{ $money($value) }}</strong></div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-5">
                <div class="finance-card">
                    <h3 class="h6">Control cockpit</h3>
                    <div class="d-grid gap-2">
                        @foreach($financeCockpit['risk_flags'] as $risk)
                            @php($riskClass = ($risk['amount'] ?? 0) > 0 || ($risk['count'] ?? 0) > 0 ? 'warn' : '')
                            <div class="finance-risk {{ $riskClass }}">
                                <div class="d-flex justify-content-between"><strong>{{ $risk['label'] }}</strong><span>{{ $risk['count'] }}</span></div>
                                @if(!is_null($risk['amount']))<div class="small finance-muted">{{ $money($risk['amount']) }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="finance-card">
                    <h3 class="h6">Top project profitability</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Project</th><th>Revenue</th><th>Expenses</th><th>Profit</th><th>Margin</th></tr></thead>
                        <tbody>
                            @forelse($costReport['projectRows']->sortByDesc('profit')->take(10) as $projectRow)
                                <tr>
                                    <td>{{ $projectRow['project']->project_name }}</td>
                                    <td>{{ $money($projectRow['revenue']) }}</td>
                                    <td>{{ $money($projectRow['expenses']) }}</td>
                                    <td>{{ $money($projectRow['profit']) }}</td>
                                    <td>{{ number_format($projectRow['margin'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No project activity yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-industry">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="finance-card">
                    <h3 class="h6">Industry finance</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Industry / Module</th><th>Invoices</th><th>Revenue</th><th>Paid</th><th>Outstanding</th><th>Overdue</th></tr></thead>
                        <tbody>
                            @forelse($financeCockpit['industry_rows'] as $row)
                                <tr>
                                    <td><strong>{{ $row['label'] }}</strong><small class="d-block text-muted">{{ $row['module'] }}</small></td>
                                    <td>{{ $row['count'] }}</td>
                                    <td>{{ $money($row['revenue']) }}</td>
                                    <td>{{ $money($row['paid']) }}</td>
                                    <td>{{ $money($row['outstanding']) }}</td>
                                    <td>{{ $money($row['overdue']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No industry-tagged invoices yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="finance-card">
                    <h3 class="h6">Invoice pipeline</h3>
                    @forelse($financeCockpit['invoice_pipeline'] as $row)
                        <div class="finance-row">
                            <span>{{ $row['status'] }} <span class="finance-pill">{{ $row['count'] }}</span></span>
                            <strong>{{ $money($row['balance']) }}</strong>
                        </div>
                    @empty
                        <div class="text-muted">No invoices yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Top customers</h3>
                    @forelse($financeCockpit['top_clients'] as $client)
                        <div class="finance-row"><span>{{ $client['name'] }}</span><strong>{{ $money($client['revenue']) }}</strong></div>
                    @empty
                        <div class="text-muted">No customer revenue yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Top suppliers</h3>
                    @forelse($financeCockpit['top_suppliers'] as $supplier)
                        <div class="finance-row"><span>{{ $supplier['name'] }}</span><strong>{{ $money($supplier['spend']) }}</strong></div>
                    @empty
                        <div class="text-muted">No supplier spend yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-gl">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="finance-card">
                    <h3 class="h6">Post journal</h3>
                    <form method="post" action="{{ route('finance.journals.store') }}" class="row g-2">
                        @csrf
                        <input class="form-control" type="date" name="entry_date" value="{{ now()->toDateString() }}" required>
                        <input class="form-control" name="description" placeholder="Journal description" required>
                        @foreach([1, 2] as $journalRow)
                            <div class="col-6"><select class="form-select" name="account_id[]" required><option value="">Account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
                            <div class="col-3"><input class="form-control" name="debit[]" type="number" step="0.01" placeholder="Debit"></div>
                            <div class="col-3"><input class="form-control" name="credit[]" type="number" step="0.01" placeholder="Credit"></div>
                        @endforeach
                        <select class="form-select" name="project_id"><option value="">No project</option>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->project_name }}</option>@endforeach</select>
                        <label class="form-check"><input class="form-check-input" type="checkbox" name="is_recurring" value="1"> <span class="form-check-label">Recurring monthly</span></label>
                        <input type="hidden" name="recurrence" value="Monthly">
                        <button class="btn btn-warning">Post balanced journal</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="finance-card">
                    <h3 class="h6">Journal entries</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Date / Number</th><th>Description</th><th>Debit</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach($journals as $journal)
                                <tr>
                                    <td>{{ $journal->entry_date->format('d M Y') }}<small class="d-block">{{ $journal->entry_number }}</small></td>
                                    <td>{{ $journal->description }}</td>
                                    <td>{{ $money($journal->total_debit) }}</td>
                                    <td><span class="finance-pill">{{ $journal->status }}</span></td>
                                    <td>
                                        @if($journal->status === 'Posted')
                                            <form method="post" action="{{ route('finance.journals.reverse', $journal) }}" class="d-flex gap-1">@csrf<input class="form-control form-control-sm" name="reason" placeholder="Reason" required><button class="btn btn-sm btn-outline-danger">Reverse</button></form>
                                        @elseif($journal->status === 'Reversed' && auth()->user()->hasPermission('finance.gl.unreverse'))
                                            <form method="post" action="{{ route('finance.journals.unreverse', $journal) }}" class="d-flex gap-1">@csrf<input class="form-control form-control-sm" name="reason" placeholder="Restoration reason" required><button class="btn btn-sm btn-outline-success">Unreverse</button></form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-coa">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="finance-card">
                    <h3 class="h6">Custom account</h3>
                    <form method="post" action="{{ route('finance.accounts.store') }}" class="row g-2">
                        @csrf
                        <div class="col-4"><input class="form-control" name="code" placeholder="Code" required></div>
                        <div class="col-8"><input class="form-control" name="name" placeholder="Name" required></div>
                        <select class="form-select" name="type">@foreach(\App\Services\FinanceService::ACCOUNT_TYPES as $type)<option>{{ $type }}</option>@endforeach</select>
                        <input class="form-control" name="subtype" placeholder="Account subtype">
                        <select class="form-select" name="parent_id"><option value="">No parent</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                        <input class="form-control" name="tax_treatment" placeholder="Tax treatment">
                        <input class="form-control" name="opening_balance" type="number" step="0.01" placeholder="Opening balance">
                        <textarea class="form-control" name="description" placeholder="Description"></textarea>
                        <button class="btn btn-warning">Create account</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="finance-card">
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Balance</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach($accounts as $account)
                                @php($line = $lines->firstWhere('id', $account->id))
                                @php($debitNormal = in_array($account->type, ['Asset','Cost of Sales','Expense','Other Expense'], true))
                                <tr>
                                    <td>{{ $account->code }}</td>
                                    <td>{{ $account->name }} @if($account->is_system)<span class="badge bg-light text-dark">System</span>@endif<small class="d-block text-muted">{{ $account->subtype }}</small></td>
                                    <td>{{ $account->type }}</td>
                                    <td>{{ $money($line ? ($debitNormal ? $line->debit - $line->credit : $line->credit - $line->debit) : $account->opening_balance) }}</td>
                                    <td><span class="badge {{ $account->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        <details>
                                            <summary class="btn btn-sm btn-outline-dark">Edit</summary>
                                            <form method="post" action="{{ route('finance.accounts.update', $account) }}" class="mt-2 text-start">
                                                @csrf @method('PUT')
                                                <input class="form-control form-control-sm mb-1" name="code" value="{{ $account->code }}" required>
                                                <input class="form-control form-control-sm mb-1" name="name" value="{{ $account->name }}" required>
                                                <select class="form-select form-select-sm mb-1" name="type">@foreach(\App\Services\FinanceService::ACCOUNT_TYPES as $type)<option @selected($account->type === $type)>{{ $type }}</option>@endforeach</select>
                                                <input class="form-control form-control-sm mb-1" name="subtype" value="{{ $account->subtype }}" placeholder="Account subtype">
                                                <select class="form-select form-select-sm mb-1" name="parent_id"><option value="">No parent</option>@foreach($accounts->where('id', '!=', $account->id) as $parent)<option value="{{ $parent->id }}" @selected($account->parent_id === $parent->id)>{{ $parent->code }} {{ $parent->name }}</option>@endforeach</select>
                                                <input class="form-control form-control-sm mb-1" name="tax_treatment" value="{{ $account->tax_treatment }}" placeholder="Tax treatment">
                                                <input class="form-control form-control-sm mb-1" name="opening_balance" type="number" step="0.01" value="{{ $account->opening_balance }}">
                                                <textarea class="form-control form-control-sm mb-1" name="description" placeholder="Description">{{ $account->description }}</textarea>
                                                <button class="btn btn-sm btn-warning">Save</button>
                                            </form>
                                            @if($account->is_active)<form method="post" action="{{ route('finance.accounts.deactivate', $account) }}" class="mt-1">@csrf<button class="btn btn-sm btn-outline-danger">Deactivate</button></form>@endif
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-ar">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Customer statements / outstanding invoices</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Customer</th><th>Invoice</th><th>Industry</th><th>Due</th><th>Balance</th></tr></thead>
                        <tbody>
                            @forelse($ar as $invoice)
                                <tr>
                                    <td>{{ $invoice->client?->name }}</td>
                                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a><small class="d-block text-muted">{{ $invoice->industry_reference }}</small></td>
                                    <td>{{ $invoice->industry_module ? \Illuminate\Support\Str::headline($invoice->industry_module) : 'Shared' }}</td>
                                    <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                                    <td>{{ $money($invoice->balance) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No outstanding receivables.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Supplier statements / outstanding bills</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Supplier</th><th>Bill</th><th>Due</th><th>Balance</th></tr></thead>
                        <tbody>
                            @forelse($ap as $bill)
                                <tr><td>{{ $bill->supplier?->name }}</td><td>{{ $bill->invoice_number }}</td><td>{{ $bill->due_date?->format('d M Y') }}</td><td>{{ $money($bill->total - $bill->amount_paid) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No outstanding payables.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-cash">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="finance-card">
                    <h3 class="h6">New cash/bank account</h3>
                    <form method="post" action="{{ route('finance.banks.store') }}" class="row g-2">
                        @csrf
                        <input class="form-control" name="name" placeholder="Account name" required>
                        <select class="form-select" name="type">@foreach(['Bank','Cash','Petty Cash','Mobile Money','MPesa'] as $type)<option>{{ $type }}</option>@endforeach</select>
                        <select class="form-select" name="finance_account_id">@foreach($accounts->where('type', 'Asset') as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                        <input class="form-control" name="institution" placeholder="Institution">
                        <input class="form-control" name="account_number" placeholder="Account number">
                        <input type="hidden" name="currency" value="KES">
                        <input class="form-control" name="opening_balance" type="number" step="0.01" placeholder="Opening balance">
                        <button class="btn btn-warning">Create</button>
                    </form>
                </div>
                <div class="finance-card mt-3">
                    <h3 class="h6">Post bank transaction</h3>
                    <form method="post" action="{{ route('finance.bank-transactions.store') }}" class="row g-2">
                        @csrf
                        <select class="form-select" name="bank_account_id" required><option value="">Bank account</option>@foreach($banks as $bank)<option value="{{ $bank->id }}">{{ $bank->name }}</option>@endforeach</select>
                        <select class="form-select" name="contra_account_id" required><option value="">Contra account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                        <div class="col-6"><input class="form-control" name="transaction_date" type="date" value="{{ now()->toDateString() }}" required></div>
                        <div class="col-6"><select class="form-select" name="type"><option>Deposit</option><option>Withdrawal</option><option>Transfer In</option><option>Transfer Out</option></select></div>
                        <input class="form-control" name="reference" placeholder="Reference">
                        <input class="form-control" name="description" placeholder="Description" required>
                        <input class="form-control" name="amount" type="number" step="0.01" min="0.01" placeholder="Amount" required>
                        <button class="btn btn-dark">Post transaction</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="finance-card">
                    <h3 class="h6">Cash position & reconciliation</h3>
                    @forelse($financeCockpit['bank_summary'] as $bank)
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $bank['name'] }}</strong><span class="float-end">{{ $money($bank['balance']) }}</span>
                            <small class="d-block text-muted">{{ $bank['type'] }} · {{ $bank['currency'] }} · {{ $bank['unreconciled'] }} unreconciled</small>
                        </div>
                    @empty
                        <div class="text-muted">No bank or cash accounts yet.</div>
                    @endforelse
                </div>
                <div class="finance-card mt-3">
                    <h3 class="h6">Recent unreconciled transactions</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Date</th><th>Account</th><th>Description</th><th>Amount</th><th></th></tr></thead>
                        <tbody>
                            @forelse($banks->flatMap->transactions->where('is_reconciled', false)->sortByDesc('transaction_date')->take(12) as $transaction)
                                <tr>
                                    <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                                    <td>{{ $transaction->bankAccount?->name }}</td>
                                    <td>{{ $transaction->description }}</td>
                                    <td>{{ $money($transaction->amount) }}</td>
                                    <td><form method="post" action="{{ route('finance.bank-transactions.reconcile', $transaction) }}">@csrf<button class="btn btn-sm btn-outline-success">Reconcile</button></form></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No unreconciled bank transactions.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-assets">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="finance-card">
                    <h3 class="h6">Create asset</h3>
                    <form method="post" action="{{ route('finance.assets.store') }}" class="row g-2">
                        @csrf
                        <input class="form-control" name="asset_number" placeholder="Asset number" required>
                        <input class="form-control" name="name" placeholder="Asset name" required>
                        <input class="form-control" name="category" placeholder="Category" required>
                        <div class="col-6"><input class="form-control" name="purchase_date" type="date" value="{{ now()->toDateString() }}" required></div>
                        <div class="col-6"><input class="form-control" name="cost" type="number" step="0.01" placeholder="Cost" required></div>
                        <div class="col-6"><input class="form-control" name="residual_value" type="number" step="0.01" placeholder="Residual value"></div>
                        <div class="col-6"><input class="form-control" name="useful_life_months" type="number" min="1" placeholder="Life months" required></div>
                        <select class="form-select" name="depreciation_method"><option>Straight-line</option><option>Reducing balance</option></select>
                        <input class="form-control" name="reducing_rate" type="number" step="0.01" placeholder="Reducing rate %">
                        <select class="form-select" name="asset_account_id">@foreach($accounts->where('type', 'Asset') as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                        <select class="form-select" name="depreciation_account_id">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                        <select class="form-select" name="expense_account_id">@foreach($accounts->where('type', 'Expense') as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                        <input class="form-control" name="location" placeholder="Location">
                        <input class="form-control" name="assigned_to" placeholder="Assigned to">
                        <button class="btn btn-warning">Create asset</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="finance-card">
                    <h3 class="h6">Asset register & depreciation</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Asset</th><th>Category</th><th>Cost</th><th>Method</th><th>Accumulated depreciation</th><th>Book value</th></tr></thead>
                        <tbody>
                            @forelse($fixedAssets as $asset)
                                @php($depreciation = $asset->schedules->where('period_date', '<=', now())->sum('depreciation'))
                                <tr><td>{{ $asset->asset_number }} · {{ $asset->name }}</td><td>{{ $asset->category }}</td><td>{{ $money($asset->cost) }}</td><td>{{ $asset->depreciation_method }}</td><td>{{ $money($depreciation) }}</td><td>{{ $money($asset->cost - $depreciation) }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No fixed assets registered.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-reports">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Trial Balance</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Account</th><th>Debit</th><th>Credit</th></tr></thead>
                        <tbody>@foreach($lines as $line)<tr><td>{{ $line->code }} {{ $line->name }}</td><td>{{ $money($line->debit) }}</td><td>{{ $money($line->credit) }}</td></tr>@endforeach</tbody>
                        <tfoot><tr><th>Total</th><th>{{ $money($lines->sum('debit')) }}</th><th>{{ $money($lines->sum('credit')) }}</th></tr></tfoot>
                    </table></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="finance-card">
                    <h3 class="h6">Financial statements</h3>
                    @foreach(['Profit & Loss' => $income - $expenses, 'Total Assets' => $assets, 'Total Liabilities' => $liabilities, 'Equity' => $equity] as $label => $value)
                        <div class="finance-row"><span>{{ $label }}</span><strong>{{ $money($value) }}</strong></div>
                    @endforeach
                    <h3 class="h6 mt-3">Tax position</h3>
                    @foreach($financeCockpit['tax_position'] as $label => $value)
                        <div class="finance-row"><span>{{ $label }}</span><strong>{{ $money($value) }}</strong></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="f-controls">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="finance-card">
                    <h3 class="h6">Lock financial period</h3>
                    <form method="post" action="{{ route('finance.periods.close') }}" class="row g-2">
                        @csrf
                        <input class="form-control" name="name" placeholder="FY 2026 / July 2026" required>
                        <div class="col-6"><input class="form-control" type="date" name="starts_at" required></div>
                        <div class="col-6"><input class="form-control" type="date" name="ends_at" required></div>
                        <button class="btn btn-danger">Close and lock period</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="finance-card">
                    <h3 class="h6">Locked periods</h3>
                    <div class="table-responsive"><table class="table align-middle">
                        <thead><tr><th>Period</th><th>Dates</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($periods as $period)
                                <tr><td>{{ $period->name }}</td><td>{{ $period->starts_at }} - {{ $period->ends_at }}</td><td>{{ $period->status }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No locked periods.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                    <small class="text-muted">Financial transactions cannot be deleted. Posted journals are corrected through reasoned reversals.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
