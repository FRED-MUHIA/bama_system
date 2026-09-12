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

        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Upcoming Meetings</h2></div>
                @forelse($meetings->filter(fn ($m) => $m->status !== 'Completed')->take(4) as $meeting)
                    <div class="chama-row"><strong>{{ $meeting->title }}</strong><span>{{ $meeting->meeting_date?->format('d M Y') }}</span></div>
                @empty
                    <p class="text-muted mb-0">No upcoming meetings scheduled.</p>
                @endforelse
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Open Votes</h2></div>
                @forelse($ballots->filter(fn ($b) => in_array($b->status, ['Open', 'Active'], true))->take(4) as $ballot)
                    <div class="chama-row"><strong>{{ $ballot->title }}</strong><span>{{ $ballot->votes_count }} vote(s)</span></div>
                @empty
                    <p class="text-muted mb-0">No ballots are currently open.</p>
                @endforelse
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
                    <div class="input-group"><input class="form-control" name="national_id" placeholder="National ID"><input class="form-control" name="phone" placeholder="Phone"></div>
                    <input class="form-control" name="email" type="email" placeholder="Email">
                    <div class="input-group"><select class="form-select" name="gender"><option value="">Gender</option><option>Male</option><option>Female</option><option>Other</option></select><input class="form-control" type="date" name="date_of_birth"></div>
                    <div class="input-group"><input class="form-control" name="occupation" placeholder="Occupation"><input class="form-control" name="employer" placeholder="Employer"></div>
                    <div class="input-group"><input class="form-control" name="next_of_kin_name" placeholder="Next of kin name"><input class="form-control" name="next_of_kin_phone" placeholder="Next of kin phone"></div>
                    <input class="form-control" name="address" placeholder="Residential address">
                    <div class="input-group"><input class="form-control" type="date" name="join_date"><select class="form-select" name="membership_type"><option value="">Membership type</option><option>Founding Member</option><option>Regular Member</option><option>Associate Member</option><option>Probationary Member</option></select></div>
                    <select class="form-select" name="status"><option>Applicant</option><option>Active</option><option>Suspended</option><option>Inactive</option><option>Exited</option><option>Deceased</option></select>
                    <button class="btn btn-success">Save Member</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Member Register</h2></div>
                <div class="table-responsive"><table class="table">
                    <thead><tr><th>Member</th><th>Phone</th><th>Savings</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>{{ $member->member_number }}<small>{{ $member->full_name }}</small></td>
                                <td>{{ $member->phone }}</td>
                                <td>{{ $money($member->savingsAccounts->sum('current_balance')) }}</td>
                                <td>{{ $member->status }}</td>
                                <td class="text-end">
                                    @if($member->status === 'Applicant')
                                        <form method="post" action="{{ route('chama.members.approve', $member) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No members yet.</td></tr>
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
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" required><select class="form-select" name="frequency"><option>Monthly</option><option>Weekly</option><option>Daily</option><option>Quarterly</option><option>Annual</option><option>One-Time</option></select></div>
                    <div class="input-group"><input class="form-control" name="fund_bucket" value="General"><input class="form-control" type="number" step="0.01" name="late_penalty" placeholder="Late penalty"></div>
                    <input class="form-control" type="number" name="grace_period_days" placeholder="Grace period (days)">
                    <label class="chama-check"><input type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory', true) ? 'checked' : '' }}> Mandatory contribution</label>
                    <button class="btn btn-success">Save Type</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Generate Contribution Schedules</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'contribution-schedules') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="contribution_type_id" required><option value="">Contribution type</option>@foreach($contributionTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                    <div class="input-group"><input class="form-control" type="date" name="start_date"><input class="form-control" type="number" name="periods" placeholder="Number of periods" required></div>
                    <button class="btn btn-outline-success">Generate Schedule</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Record Contribution</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'contributions') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="member_id"><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="contribution_type_id"><option value="">Type</option>@foreach($contributionTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                    <input class="form-control" type="number" step="0.01" name="amount_paid" placeholder="Amount paid" required>
                    <div class="input-group"><input class="form-control" type="date" name="payment_date"><select class="form-select" name="payment_method"><option>M-PESA</option><option>Cash</option><option>Bank Transfer</option><option>Card</option><option>Other</option></select></div>
                    <input class="form-control" name="payment_reference" placeholder="Reference">
                    <button class="btn btn-success">Record Payment</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Recent Contributions</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>No.</th><th>Member</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead><tbody>
                    @forelse($contributions as $contribution)
                        <tr><td>{{ $contribution->contribution_number }}</td><td>{{ $contribution->member?->full_name }}</td><td>{{ $contribution->contributionType?->name }}</td><td>{{ $money($contribution->amount_paid) }}</td><td>{{ $contribution->status }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No contributions yet.</td></tr>
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
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="opening_balance" placeholder="Opening balance"><input class="form-control" type="number" step="0.01" name="interest_rate" placeholder="Interest rate"></div>
                    <button class="btn btn-success">Open Account</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Savings Transaction</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'savings-transactions') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="savings_account_id" required><option value="">Savings account</option>@foreach($savingsAccounts as $account)<option value="{{ $account->id }}">{{ $account->account_number }} ({{ $account->member?->full_name }})</option>@endforeach</select>
                    <select class="form-select" name="transaction_type" required><option>Deposit</option><option>Withdrawal</option><option>Transfer In</option><option>Transfer Out</option><option>Interest</option></select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" required><input class="form-control" type="date" name="transaction_date"></div>
                    <select class="form-select" name="payment_method"><option value="">Payment method</option><option>M-PESA</option><option>Cash</option><option>Bank Transfer</option><option>Card</option><option>Other</option></select>
                    <input class="form-control" name="reference" placeholder="Reference">
                    <button class="btn btn-outline-success">Record Transaction</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Savings Accounts</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Account</th><th>Member</th><th>Balance</th><th>Interest</th><th>Status</th></tr></thead><tbody>
                    @forelse($savingsAccounts as $account)
                        <tr><td>{{ $account->account_number }}</td><td>{{ $account->member?->full_name }}</td><td>{{ $money($account->current_balance) }}</td><td>{{ $account->interest_rate }}</td><td>{{ $account->status }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No savings accounts yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'merry-go-round')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Start Cycle</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'merry-go-round-cycles') }}" class="row g-2">
                    @csrf
                    <input class="form-control" name="name" placeholder="Cycle name" required>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="contribution_amount" placeholder="Contribution amount" required><select class="form-select" name="frequency"><option>Weekly</option><option>Monthly</option><option>Custom</option></select></div>
                    <div class="input-group"><input class="form-control" type="date" name="start_date"><input class="form-control" type="date" name="next_payout_date"></div>
                    <input class="form-control" type="number" step="0.01" name="payout_amount" placeholder="Payout amount">
                    <select class="form-select" name="status"><option>Planned</option><option>Active</option><option>Completed</option><option>Suspended</option></select>
                    <select class="form-select" name="member_ids[]" multiple size="6"><option value="" disabled>Select members (multi)</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <button class="btn btn-success">Start Cycle</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Record Round / Payout</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'merry-go-round-rounds') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="cycle_id" required><option value="">Cycle</option>@foreach($cycles as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->name }}</option>@endforeach</select>
                    <select class="form-select" name="beneficiary_id" required><option value="">Beneficiary</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <div class="input-group"><input class="form-control" type="date" name="due_date"><input class="form-control" type="date" name="payout_date"></div>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="expected_amount" placeholder="Expected amount"><input class="form-control" type="number" step="0.01" name="amount_collected" placeholder="Amount collected"></div>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="payout_amount" placeholder="Payout amount"><select class="form-select" name="payout_method"><option value="">Payout method</option><option>M-PESA</option><option>Cash</option><option>Bank Transfer</option><option>Cheque</option></select></div>
                    <input class="form-control" name="transaction_reference" placeholder="Transaction reference">
                    <button class="btn btn-outline-success">Record Round</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Active Cycles</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Cycle</th><th>Contribution</th><th>Payout</th><th>Next Payout</th><th>Status</th></tr></thead><tbody>
                    @forelse($cycles as $cycle)
                        <tr><td>{{ $cycle->cycle_number }}<small>{{ $cycle->name }}</small></td><td>{{ $money($cycle->contribution_amount) }}</td><td>{{ $money($cycle->payout_amount) }}</td><td>{{ $cycle->next_payout_date?->format('d M Y') }}</td><td>{{ $cycle->status }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No cycles yet.</td></tr>
                    @endforelse
                </tbody></table></div>
                <div class="chama-panel-head" style="margin-top:18px"><h2>Recent Rounds</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Round</th><th>Cycle</th><th>Beneficiary</th><th>Collected</th><th>Status</th></tr></thead><tbody>
                    @forelse($rounds as $round)
                        <tr><td>{{ $round->round_number }}</td><td>{{ $round->cycle?->name }}</td><td>{{ $round->beneficiary?->full_name }}</td><td>{{ $money($round->amount_collected) }}</td><td>{{ $round->status }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No rounds recorded yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'table-banking')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Record Session</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'table-banking-sessions') }}" class="row g-2">
                    @csrf
                    <input class="form-control" type="date" name="session_date" required>
                    <select class="form-select" name="meeting_id"><option value="">Linked meeting (optional)</option>@foreach($meetings as $meeting)<option value="{{ $meeting->id }}">{{ $meeting->title }} — {{ $meeting->meeting_date?->format('d M Y') }}</option>@endforeach</select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="opening_balance" placeholder="Opening balance"><input class="form-control" type="number" step="0.01" name="contributions" placeholder="Contributions"></div>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="savings" placeholder="Savings"><input class="form-control" type="number" step="0.01" name="loan_repayments" placeholder="Loan repayments"></div>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="interest" placeholder="Interest"><input class="form-control" type="number" step="0.01" name="fines" placeholder="Fines"></div>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="expenses" placeholder="Expenses"><input class="form-control" type="number" step="0.01" name="loans_disbursed" placeholder="Loans disbursed"></div>
                    <input class="form-control" type="number" step="0.01" name="closing_balance" placeholder="Closing balance" required>
                    <select class="form-select" name="status"><option>Open</option><option>Closed</option><option>Balanced</option><option>Discrepancy</option></select>
                    <button class="btn btn-success">Save Session</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Banking Sessions</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Session</th><th>Date</th><th>Contributions</th><th>Closing</th><th>Balanced</th><th>Status</th></tr></thead><tbody>
                    @forelse($tableSessions as $session)
                        <tr>
                            <td>{{ $session->session_number }}</td>
                            <td>{{ $session->session_date?->format('d M Y') }}</td>
                            <td>{{ $money($session->contributions) }}</td>
                            <td>{{ $money($session->closing_balance) }}</td>
                            <td>{{ $session->is_balanced ? 'Yes' : 'No' }}</td>
                            <td>{{ $session->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No banking sessions yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'loans')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Loan Product</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'loan-products') }}" class="row g-2 mb-3">
                    @csrf
                    <input class="form-control" name="name" placeholder="Loan product name" required>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="interest_rate" placeholder="Interest rate"><input class="form-control" type="number" name="term_months" placeholder="Term months" value="1" required></div>
                    <select class="form-select" name="interest_method"><option>Flat Rate</option><option>Reducing Balance</option><option>Fixed Charge</option><option>Interest-Free</option></select>
                    <button class="btn btn-outline-success">Save Product</button>
                </form>
                <div class="chama-panel-head"><h2>Loan Application</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'loans') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="loan_product_id" required><option value="">Loan product</option>@foreach($loanProducts as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>
                    <select class="form-select" name="member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <input class="form-control" type="number" step="0.01" name="principal" placeholder="Principal" required>
                    <input class="form-control" name="purpose" placeholder="Purpose">
                    <button class="btn btn-success">Create Loan</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Record Loan Repayment</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'loan-repayments') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="loan_id" required><option value="">Loan</option>@foreach($loans as $loan)<option value="{{ $loan->id }}">{{ $loan->loan_number }} ({{ $loan->member?->full_name }})</option>@endforeach</select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" required><input class="form-control" type="date" name="payment_date"></div>
                    <select class="form-select" name="payment_method" required><option>M-PESA</option><option>Cash</option><option>Bank Transfer</option><option>Card</option><option>Savings Offset</option><option>Other</option></select>
                    <input class="form-control" name="reference" placeholder="Reference">
                    <button class="btn btn-outline-success">Record Repayment</button>
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
                                @elseif($loan->status === 'Approved')
                                    <form method="post" action="{{ route('chama.loans.disburse', $loan) }}">@csrf<button class="btn btn-sm btn-success">Disburse</button></form>
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

    @if($activeTab === 'fines')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Issue Fine</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'fines') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="fine_type" required><option value="">Fine type</option><option>Late Payment</option><option>Lateness to Meeting</option><option>Absent without Notice</option><option>Rule Violation</option><option>Cheque Bounce</option><option>Other</option></select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" required><input class="form-control" type="date" name="fine_date" required></div>
                    <input class="form-control" name="reason" placeholder="Reason">
                    <select class="form-select" name="status"><option>Pending</option><option>Paid</option><option>Waived</option><option>Appealed</option></select>
                    <button class="btn btn-success">Issue Fine</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Fine Register</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Fine</th><th>Member</th><th>Type</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead><tbody>
                    @forelse($fines as $fine)
                        <tr>
                            <td>{{ $fine->fine_number }}</td>
                            <td>{{ $fine->member?->full_name }}</td>
                            <td>{{ $fine->fine_type }}</td>
                            <td>{{ $money($fine->amount) }}</td>
                            <td>{{ $money($fine->amount_paid) }}</td>
                            <td>{{ $fine->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No fines recorded yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'welfare')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Welfare Request</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'welfare-requests') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="request_type" required><option value="">Request type</option><option>Medical</option><option>Funeral</option><option>Education</option><option>Emergency</option><option>Birth</option><option>Wedding</option><option>Other</option></select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="amount_requested" placeholder="Amount requested" required><input class="form-control" type="number" step="0.01" name="amount_approved" placeholder="Amount approved"></div>
                    <input class="form-control" name="reason" placeholder="Reason">
                    <select class="form-select" name="status"><option>Submitted</option><option>Under Review</option><option>Approved</option><option>Paid</option><option>Rejected</option></select>
                    <button class="btn btn-success">Submit Request</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Welfare Requests</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Request</th><th>Member</th><th>Type</th><th>Requested</th><th>Approved</th><th>Status</th></tr></thead><tbody>
                    @forelse($welfareRequests as $request)
                        <tr>
                            <td>{{ $request->request_number }}</td>
                            <td>{{ $request->member?->full_name }}</td>
                            <td>{{ $request->request_type }}</td>
                            <td>{{ $money($request->amount_requested) }}</td>
                            <td>{{ $money($request->amount_approved) }}</td>
                            <td>{{ $request->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No welfare requests yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'shares')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Share Transaction</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'shares') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="transaction_type" required><option>Purchase</option><option>Transfer In</option><option>Transfer Out</option><option>Withdrawal</option></select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="share_units" placeholder="Share units" required><input class="form-control" type="number" step="0.01" name="price_per_share" placeholder="Price per share" required></div>
                    <div class="input-group"><input class="form-control" type="date" name="transaction_date"><select class="form-select" name="status"><option>Pending</option><option>Approved</option><option>Completed</option><option>Rejected</option></select></div>
                    <input class="form-control" name="notes" placeholder="Notes">
                    <button class="btn btn-success">Record Transaction</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Share Transactions</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Transaction</th><th>Member</th><th>Type</th><th>Units</th><th>Value</th><th>Status</th></tr></thead><tbody>
                    @forelse($shareTransactions as $share)
                        <tr>
                            <td>{{ $share->transaction_number }}</td>
                            <td>{{ $share->member?->full_name }}</td>
                            <td>{{ $share->transaction_type }}</td>
                            <td>{{ $share->share_units }}</td>
                            <td>{{ $money(((float) $share->share_units * (float) $share->price_per_share)) }}</td>
                            <td>{{ $share->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No share transactions yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'investments')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Create Investment</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'investments') }}" class="row g-2">
                    @csrf
                    <input class="form-control" name="name" placeholder="Investment name" required>
                    <select class="form-select" name="investment_type" required><option value="">Investment type</option><option>Land</option><option>Shares / Stocks</option><option>Treasury Bills</option><option>SACCO Shares</option><option>Fixed Deposit</option><option>Business Venture</option><option>Other</option></select>
                    <div class="input-group"><input class="form-control" type="date" name="purchase_date"><select class="form-select" name="allocation_basis"><option value="">Allocation basis</option><option>Equal Shares</option><option>Share Capital Ratio</option><option>Contribution Ratio</option><option>Custom Allocation</option></select></div>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="initial_cost" placeholder="Initial cost"><input class="form-control" type="number" step="0.01" name="current_value" placeholder="Current value"></div>
                    <select class="form-select" name="status"><option>Active</option><option>Pending</option><option>Held</option><option>Sold</option></select>
                    <button class="btn btn-success">Create Investment</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Record Investment Income</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'investment-income') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="investment_id" required><option value="">Investment</option>@foreach($investments as $investment)<option value="{{ $investment->id }}">{{ $investment->name }}</option>@endforeach</select>
                    <select class="form-select" name="income_type" required><option value="">Income type</option><option>Interest</option><option>Rent</option><option>Dividends</option><option>Capital Gain</option><option>Sale Proceeds</option></select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" required><input class="form-control" type="date" name="income_date"></div>
                    <select class="form-select" name="allocation_status"><option>Pending Allocation</option><option>Allocated</option><option>Reserved</option></select>
                    <input class="form-control" name="notes" placeholder="Notes">
                    <button class="btn btn-outline-success">Record Income</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Investment Portfolio</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Investment</th><th>Type</th><th>Cost</th><th>Value</th><th>Status</th></tr></thead><tbody>
                    @forelse($investments as $investment)
                        <tr><td>{{ $investment->investment_number }}<small>{{ $investment->name }}</small></td><td>{{ $investment->investment_type }}</td><td>{{ $money($investment->initial_cost) }}</td><td>{{ $money($investment->current_value) }}</td><td>{{ $investment->status }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No investments yet.</td></tr>
                    @endforelse
                </tbody></table></div>
                <div class="chama-panel-head" style="margin-top:18px"><h2>Run Dividend</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'dividends') }}" class="row g-2 mb-3">
                    @csrf
                    <input class="form-control" name="period" placeholder="Period (e.g. 2026 Q1)" required>
                    <select class="form-select" name="distribution_basis" required><option value="">Distribution basis</option><option>Share Capital</option><option>Savings</option><option>Contributions</option><option>Investment Units</option><option>Custom Formula</option></select>
                    <div class="input-group"><input class="form-control" type="number" step="0.01" name="profit_available" placeholder="Profit available" required><input class="form-control" type="number" step="0.01" name="reserve_allocation" placeholder="Reserve allocation"></div>
                    <button class="btn btn-outline-success">Run Dividend</button>
                </form>
                <div class="chama-panel-head"><h2>Dividend Runs</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Run</th><th>Period</th><th>Basis</th><th>Profit</th><th>Allocations</th><th>Status</th></tr></thead><tbody>
                    @forelse($dividendRuns as $run)
                        <tr><td>{{ $run->dividend_number }}</td><td>{{ $run->period }}</td><td>{{ $run->distribution_basis }}</td><td>{{ $money($run->profit_available) }}</td><td>{{ $run->allocations_count }}</td><td>{{ $run->status }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No dividend runs yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'meetings')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Schedule Meeting</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'meetings') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="meeting_type" required><option value="">Meeting type</option><option>Monthly General</option><option>Annual General</option><option>Special General</option><option>Executive Committee</option><option>Election</option><option>Other</option></select>
                    <input class="form-control" name="title" placeholder="Meeting title" required>
                    <div class="input-group"><input class="form-control" type="date" name="meeting_date" required><input class="form-control" type="time" name="meeting_time"></div>
                    <div class="input-group"><input class="form-control" name="venue" placeholder="Venue"><input class="form-control" name="online_link" placeholder="Online link"></div>
                    <textarea class="form-control" name="agenda" rows="3" placeholder="Agenda (one item per line)"></textarea>
                    <div class="input-group"><select class="form-select" name="chairperson_id"><option value="">Chairperson</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select><select class="form-select" name="secretary_id"><option value="">Secretary</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select></div>
                    <div class="input-group"><input class="form-control" type="number" name="quorum_required_count" placeholder="Quorum (count)"><input class="form-control" type="number" step="0.01" name="quorum_required_percent" placeholder="Quorum (%)"></div>
                    <select class="form-select" name="status"><option>Planned</option><option>Scheduled</option><option>In Progress</option><option>Completed</option><option>Cancelled</option></select>
                    <button class="btn btn-success">Schedule Meeting</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Mark Attendance</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'attendance') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="meeting_id" required><option value="">Meeting</option>@foreach($meetings as $meeting)<option value="{{ $meeting->id }}">{{ $meeting->title }} — {{ $meeting->meeting_date?->format('d M Y') }}</option>@endforeach</select>
                    <select class="form-select" name="member_id" required><option value="">Member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="status" required><option value="">Attendance</option><option>Present</option><option>Absent</option><option>Late</option><option>Excused</option></select>
                    <input class="form-control" type="number" step="0.01" name="fine_amount" placeholder="Fine amount (if any)">
                    <button class="btn btn-outline-success">Save Attendance</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Meetings</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Meeting</th><th>Date</th><th>Venue</th><th>Attended</th><th>Status</th></tr></thead><tbody>
                    @forelse($meetings as $meeting)
                        <tr><td>{{ $meeting->meeting_number }}<small>{{ $meeting->title }}</small></td><td>{{ $meeting->meeting_date?->format('d M Y') }}</td><td>{{ $meeting->venue }}</td><td>{{ $meeting->attendance_count }}</td><td>{{ $meeting->status }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No meetings scheduled yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if($activeTab === 'voting')
        <section class="chama-grid two">
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Create Ballot</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'ballots') }}" class="row g-2">
                    @csrf
                    <input class="form-control" name="title" placeholder="Ballot title" required>
                    <select class="form-select" name="vote_type" required><option value="">Vote type</option><option>Yes / No</option><option>Multiple Choice</option><option>Candidate Election</option></select>
                    <select class="form-select" name="meeting_id"><option value="">Linked meeting (optional)</option>@foreach($meetings as $meeting)<option value="{{ $meeting->id }}">{{ $meeting->title }}</option>@endforeach</select>
                    <textarea class="form-control" name="options_text" rows="4" placeholder="Options (one per line, e.g. candidate names)"></textarea>
                    <label class="chama-check"><input type="checkbox" name="is_secret" value="1"> Secret ballot</label>
                    <div class="input-group"><input class="form-control" type="date" name="opens_at"><input class="form-control" type="date" name="closes_at"></div>
                    <select class="form-select" name="status"><option>Draft</option><option>Open</option><option>Closed</option><option>Result Published</option></select>
                    <button class="btn btn-success">Create Ballot</button>
                </form>
                <hr>
                <div class="chama-panel-head"><h2>Cast Vote</h2></div>
                <form method="post" action="{{ route('chama.records.store', 'votes') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="ballot_id" required><option value="">Ballot</option>@foreach($ballots as $ballot)<option value="{{ $ballot->id }}">{{ $ballot->title }}</option>@endforeach</select>
                    <select class="form-select" name="member_id" required><option value="">Voting member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <select class="form-select" name="candidate_member_id"><option value="">Candidate / option (optional)</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select>
                    <input class="form-control" name="vote_value" placeholder="Vote value (e.g. Yes, or option text)">
                    <button class="btn btn-outline-success">Cast Vote</button>
                </form>
            </div>
            <div class="chama-panel">
                <div class="chama-panel-head"><h2>Ballots</h2></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Ballot</th><th>Type</th><th>Ballot Number</th><th>Votes</th><th>Status</th></tr></thead><tbody>
                    @forelse($ballots as $ballot)
                        <tr><td>{{ $ballot->title }}</td><td>{{ $ballot->vote_type }}</td><td>{{ $ballot->ballot_number }}</td><td>{{ $ballot->votes_count }}</td><td>{{ $ballot->status }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No ballots created yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if(in_array($activeTab, ['documents', 'settings'], true))
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
                @if($activeTab === 'documents')
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
    .chama-check{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:700;color:#374151;padding:2px 4px}
    @media(max-width:1400px){.chama-kpis{grid-template-columns:repeat(4,minmax(0,1fr))}}
    @media(max-width:1200px){.chama-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.chama-grid.two{grid-template-columns:1fr}.chama-hero{align-items:flex-start;flex-direction:column}.chama-module-strip{justify-content:flex-start}}
    @media(max-width:991px){
        .chama-page form .input-group{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:.5rem;row-gap:.5rem;flex-wrap:unset}
        .chama-page form .input-group>.form-control,.chama-page form .input-group>.form-select{flex:1 1 100%;width:100%;min-width:0;margin-left:0;border-radius:9px !important}
        .chama-page form .btn:not(.btn-sm){width:auto;min-width:0;min-height:46px;padding:.62rem 1.15rem;margin-inline:auto;white-space:normal}
        .chama-page .table-responsive{-webkit-overflow-scrolling:touch}
    }
    @media(max-width:480px){
        .chama-page .chama-hero{padding:16px;gap:14px}
        .chama-page .chama-hero h1{font-size:1.4rem}
        .chama-page .chama-hero span{font-size:.8rem;line-height:1.45;display:block}
        .chama-panel{padding:14px}
        .chama-panel-head h2{font-size:.98rem}
        .chama-page form .input-group{grid-template-columns:1fr}
        .chama-page form textarea.form-control{min-height:96px}
        .chama-actions a{flex:1 1 44%;min-width:0;text-align:center}
        .chama-kpis{grid-template-columns:1fr}
    }
</style>
@endsection