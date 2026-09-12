@extends('layouts.app')

@section('title', 'Chama Management')

@section('content')
@php
    $activeTab = request('section', 'dashboard');
    $money = fn ($value) => number_format((float) $value, 2);
    $tabs = [
        'dashboard' => ['Dashboard', 'bi-speedometer2'],
        'members' => ['Members', 'bi-people'],
        'contributions' => ['Contributions', 'bi-wallet2'],
        'savings' => ['Savings', 'bi-piggy-bank'],
        'merry-go-round' => ['Merry-Go-Round', 'bi-arrow-repeat'],
        'table-banking' => ['Table Banking', 'bi-table'],
        'loans' => ['Loans', 'bi-cash-coin'],
        'fines' => ['Fines', 'bi-exclamation-triangle'],
        'welfare' => ['Welfare', 'bi-heart'],
        'shares' => ['Shares', 'bi-pie-chart'],
        'investments' => ['Investments', 'bi-graph-up-arrow'],
        'meetings' => ['Meetings', 'bi-calendar-event'],
        'voting' => ['Voting', 'bi-check2-square'],
        'documents' => ['Documents', 'bi-folder2-open'],
        'reports' => ['Reports', 'bi-bar-chart'],
        'settings' => ['Settings', 'bi-gear'],
    ];
@endphp

<div class="chama-page">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="chama-hero">
        <div>
            <p>Industry Workspace</p>
            <h1>Chama Management</h1>
            <span>{{ $subIndustryName }}</span>
        </div>
        <div class="chama-module-strip">
            @foreach(array_slice($activeModules, 0, 10) as $module)
                <span>{{ $module }}</span>
            @endforeach
        </div>
    </section>

    <nav class="chama-tabs" aria-label="Chama workspace sections">
        @foreach($tabs as $id => [$label, $icon])
            <a class="{{ $activeTab === $id ? 'active' : '' }}" href="{{ route('chama.dashboard', ['section' => $id]) }}">
                <i class="bi {{ $icon }}"></i>{{ $label }}
            </a>
        @endforeach
    </nav>

    @if($activeTab === 'dashboard')
        <section class="chama-kpis">
            @foreach([
                ['Total Members', $metrics['total_members'] ?? 0],
                ['Active Members', $metrics['active_members'] ?? 0],
                ['Total Contributions', $money($metrics['total_contributions'] ?? 0)],
                ['Total Savings', $money($metrics['total_savings'] ?? 0)],
                ['Outstanding Loans', $money($metrics['outstanding_loans'] ?? 0)],
                ['Welfare Fund', $money($metrics['welfare_fund_balance'] ?? 0)],
                ['Investment Value', $money($metrics['investment_value'] ?? 0)],
                ['M-PESA Collections', $money($metrics['mpesa_collections'] ?? 0)],
            ] as [$label, $value])
                <div class="chama-card"><small>{{ $label }}</small><strong>{{ $value }}</strong></div>
            @endforeach
        </section>

        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Operational Alerts</h2></div>
                @forelse($alerts as $alert)
                    <div class="chama-row"><strong>{{ $alert['type'] }}</strong><span>{{ $alert['severity'] }}</span></div>
                @empty
                    <p class="text-muted mb-0">No current Chama alerts.</p>
                @endforelse
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Quick Setup</h2></div>
                <div class="chama-actions">
                    @foreach(['members' => 'Add Member', 'contributions' => 'Record Contribution', 'savings' => 'Open Savings', 'loans' => 'Create Loan', 'meetings' => 'Schedule Meeting', 'reports' => 'Export Reports'] as $section => $label)
                        <a href="{{ route('chama.dashboard', ['section' => $section]) }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($activeTab === 'members')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Member Profile</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'members') }}" class="row g-2">
                    @csrf
                    <input class="form-control" name="full_name" placeholder="Full name" required>
                    <input class="form-control" name="national_id" placeholder="National ID">
                    <input class="form-control" name="phone" placeholder="Phone">
                    <input class="form-control" name="email" type="email" placeholder="Email">
                    <input class="form-control" name="occupation" placeholder="Occupation">
                    <input class="form-control" type="date" name="join_date">
                    <select class="form-select" name="status"><option>Applicant</option><option>Active</option><option>Suspended</option><option>Exited</option></select>
                    <button class="btn btn-success">Save Member</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Member Register</h2></div>
                <div class="table-responsive"><table class="table">
                    <thead><tr><th>Member</th><th>Phone</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>{{ $member->member_number }}<small>{{ $member->full_name }}</small></td>
                                <td>{{ $member->phone }}</td>
                                <td>{{ $member->status }}</td>
                                <td class="text-end">
                                    @if($member->status === 'Applicant')
                                        <form method="post" action="{{ route('chama.members.approve', $member) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No members yet.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'contributions')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Contribution Type</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'contribution-types') }}" class="row g-2">
                    @csrf
                    <input class="form-control" name="name" placeholder="Name" required>
                    <input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" required>
                    <select class="form-select" name="frequency"><option>Monthly</option><option>Weekly</option><option>Daily</option><option>Quarterly</option><option>Annual</option><option>One-Time</option></select>
                    <input class="form-control" name="fund_bucket" value="General">
                    <button class="btn btn-success">Save Type</button>
                </form>
                <hr>
                <form method="post" action="{{ route('chama.records.store', 'contributions') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="member_id"><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="contribution_type_id"><option value="">Type</option>@foreach($contributionTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                    <input class="form-control" type="number" step="0.01" name="amount_paid" placeholder="Amount paid" required>
                    <input class="form-control" type="date" name="payment_date">
                    <select class="form-select" name="payment_method"><option>M-PESA</option><option>Cash</option><option>Bank Transfer</option><option>Card</option><option>Other</option></select>
                    <input class="form-control" name="payment_reference" placeholder="Reference">
                    <button class="btn btn-success">Record Payment</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Recent Contributions</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>No.</th><th>Member</th><th>Amount</th><th>Status</th></tr></thead><tbody>
                    @forelse($contributions as $contribution)
                        <tr><td>{{ $contribution->contribution_number }}</td><td>{{ $contribution->member?->full_name }}</td><td>{{ $money($contribution->amount_paid) }}</td><td>{{ $contribution->status }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No contributions yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'savings')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Savings Account</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'savings-accounts') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="account_type"><option>Regular Savings</option><option>Voluntary Savings</option><option>Fixed Savings</option><option>Emergency Savings</option><option>Goal-Based Savings</option></select>
                    <input class="form-control" type="number" step="0.01" name="opening_balance" placeholder="Opening balance">
                    <input class="form-control" type="number" step="0.01" name="interest_rate" placeholder="Interest rate">
                    <button class="btn btn-success">Open Account</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Savings Accounts</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Account</th><th>Member</th><th>Balance</th><th>Status</th></tr></thead><tbody>
                    @forelse($savingsAccounts as $account)
                        <tr><td>{{ $account->account_number }}</td><td>{{ $account->member?->full_name }}</td><td>{{ $money($account->current_balance) }}</td><td>{{ $account->status }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No savings accounts yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'loans')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Loan Product & Application</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'loan-products') }}" class="row g-2 mb-3">
                    @csrf
                    <input class="form-control" name="name" placeholder="Loan product name" required>
                    <input class="form-control" type="number" step="0.01" name="interest_rate" placeholder="Interest rate">
                    <input class="form-control" type="number" name="term_months" placeholder="Term months" value="1" required>
                    <select class="form-select" name="interest_method"><option>Flat Rate</option><option>Reducing Balance</option><option>Fixed Charge</option><option>Interest-Free</option></select>
                    <button class="btn btn-outline-success">Save Product</button>
                </form>
                <form method="post" action="{{ route('chama.records.store', 'loans') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="loan_product_id" required><option value="">Loan product</option>@foreach($loanProducts as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>
                    <select class="form-select" name="member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <input class="form-control" type="number" step="0.01" name="principal" placeholder="Principal" required>
                    <input class="form-control" name="purpose" placeholder="Purpose">
                    <button class="btn btn-success">Create Loan</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Loan Portfolio</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Loan</th><th>Member</th><th>Outstanding</th><th>Status</th><th></th></tr></thead><tbody>
                    @forelse($loans as $loan)
                        <tr>
                            <td>{{ $loan->loan_number }}</td>
                            <td>{{ $loan->member?->full_name }}</td>
                            <td>{{ $money((float) $loan->outstanding_principal + (float) $loan->outstanding_interest + (float) $loan->outstanding_penalties) }}</td>
                            <td>{{ $loan->status }}</td>
                            <td class="text-end">
                                @if(in_array($loan->status, ['Submitted', 'Under Review'], true))
                                    <form method="post" action="{{ route('chama.loans.approve', $loan) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No loans yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if(in_array($activeTab, ['merry-go-round', 'table-banking', 'fines', 'welfare', 'shares', 'investments', 'meetings', 'voting', 'documents', 'settings'], true))
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>{{ $tabs[$activeTab][0] }}</h2></div>
                <p class="text-muted">This Chama section is enabled for {{ $subIndustryName }} and ready for record management.</p>
                <div class="chama-actions">
                    <a href="{{ route('chama.dashboard', ['section' => 'members']) }}">Members</a>
                    <a href="{{ route('chama.dashboard', ['section' => 'contributions']) }}">Contributions</a>
                    <a href="{{ route('chama.dashboard', ['section' => 'loans']) }}">Loans</a>
                    <a href="{{ route('chama.dashboard', ['section' => 'reports']) }}">Reports</a>
                </div>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Current Records</h2></div>
                @if($activeTab === 'merry-go-round')
                    @include('chama.partials.simple-table', ['rows' => $cycles, 'columns' => ['cycle_number' => 'Cycle', 'name' => 'Name', 'status' => 'Status']])
                @elseif($activeTab === 'table-banking')
                    @include('chama.partials.simple-table', ['rows' => $tableSessions, 'columns' => ['session_number' => 'Session', 'session_date' => 'Date', 'status' => 'Status']])
                @elseif($activeTab === 'fines')
                    @include('chama.partials.simple-table', ['rows' => $fines, 'columns' => ['fine_number' => 'Fine', 'fine_type' => 'Type', 'status' => 'Status']])
                @elseif($activeTab === 'welfare')
                    @include('chama.partials.simple-table', ['rows' => $welfareRequests, 'columns' => ['request_number' => 'Request', 'request_type' => 'Type', 'status' => 'Status']])
                @elseif($activeTab === 'shares')
                    @include('chama.partials.simple-table', ['rows' => $shareTransactions, 'columns' => ['transaction_number' => 'Transaction', 'transaction_type' => 'Type', 'status' => 'Status']])
                @elseif($activeTab === 'investments')
                    @include('chama.partials.simple-table', ['rows' => $investments, 'columns' => ['investment_number' => 'Investment', 'name' => 'Name', 'status' => 'Status']])
                @elseif($activeTab === 'meetings')
                    @include('chama.partials.simple-table', ['rows' => $meetings, 'columns' => ['meeting_number' => 'Meeting', 'title' => 'Title', 'status' => 'Status']])
                @elseif($activeTab === 'voting')
                    @include('chama.partials.simple-table', ['rows' => $ballots, 'columns' => ['ballot_number' => 'Ballot', 'title' => 'Title', 'status' => 'Status']])
                @elseif($activeTab === 'documents')
                    @include('chama.partials.simple-table', ['rows' => $documents, 'columns' => ['document_number' => 'Document', 'title' => 'Title', 'status' => 'Status']])
                @else
                    @include('chama.partials.simple-table', ['rows' => $rules, 'columns' => ['rule_number' => 'Rule', 'name' => 'Name', 'status' => 'Status']])
                @endif
            </div>
        </section>
    @endif

    @if($activeTab === 'reports')
        <section class="chama-panel">
            <div class="chama-panel-head"><h2>Chama Reports</h2></div>
            <div class="chama-actions">
                @foreach(['members', 'contributions', 'arrears', 'savings', 'loans', 'fines', 'table-banking'] as $report)
                    <a href="{{ route('chama.reports.csv', $report) }}">{{ str($report)->headline() }}</a>
                @endforeach
            </div>
        </section>
    @endif
</div>

<style>
    .chama-page{display:grid;gap:16px}
    .chama-hero{display:flex;justify-content:space-between;gap:18px;align-items:center;background:#071B12;color:#fff;border-radius:10px;padding:24px;border:1px solid rgba(0,166,81,.25)}
    .chama-hero p{margin:0 0 6px;color:#8BE7B6;text-transform:uppercase;font-size:.72rem;font-weight:800;letter-spacing:.1em}
    .chama-hero h1{margin:0;font-size:2rem;font-weight:900}
    .chama-hero span{color:#d1d5db}
    .chama-module-strip{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;max-width:760px}
    .chama-module-strip span{border:1px solid rgba(255,255,255,.18);border-radius:999px;padding:.38rem .62rem;font-size:.72rem;font-weight:800;color:#fff}
    .chama-tabs{display:flex;gap:8px;overflow:auto;padding-bottom:3px}
    .chama-tabs a{display:flex;align-items:center;gap:7px;white-space:nowrap;text-decoration:none;color:#111827;border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:.55rem .72rem;font-size:.78rem;font-weight:800}
    .chama-tabs a.active{background:#00A651;border-color:#00A651;color:#fff}
    .chama-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .chama-card,.chama-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 24px rgba(15,23,42,.05)}
    .chama-card{padding:16px;min-height:92px}
    .chama-card small{display:block;color:#667085;font-weight:800;text-transform:uppercase;font-size:.66rem;letter-spacing:.06em}
    .chama-card strong{display:block;margin-top:9px;font-size:1.25rem;color:#000}
    .chama-grid{display:grid;gap:14px}
    .chama-grid.two{grid-template-columns:minmax(300px,.85fr) minmax(0,1.15fr)}
    .chama-panel{padding:18px}
    .chama-panel-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .chama-panel-head h2{font-size:1.05rem;font-weight:900;margin:0}
    .chama-row{display:flex;justify-content:space-between;gap:14px;border-top:1px solid #edf0f4;padding:11px 0}
    .chama-row:first-of-type{border-top:0}
    .chama-row span{color:#00A651;font-weight:800}
    .chama-actions{display:flex;flex-wrap:wrap;gap:9px}
    .chama-actions a{border:1px solid #d7eadf;border-radius:8px;padding:.58rem .72rem;text-decoration:none;color:#007A3B;font-weight:800;background:#EAF8F0}
    .chama-page .table small{display:block;color:#667085}
    @media(max-width:1200px){.chama-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.chama-grid.two{grid-template-columns:1fr}.chama-hero{align-items:flex-start;flex-direction:column}.chama-module-strip{justify-content:flex-start}}
    @media(max-width:640px){.chama-kpis{grid-template-columns:1fr}.chama-hero{padding:18px}.chama-hero h1{font-size:1.55rem}.chama-tabs a{font-size:.74rem}}
</style>
@endsection
