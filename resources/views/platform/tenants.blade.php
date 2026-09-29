@extends('layouts.platform')
@section('title', 'Client Management')
@section('content')
<style>
    .client-table { table-layout:fixed; width:100%; }
    .client-table th:nth-child(1) { width:18%; }
    .client-table th:nth-child(2) { width:27%; }
    .client-table th:nth-child(3) { width:14%; }
    .client-table th:nth-child(4) { width:7%; }
    .client-table th:nth-child(5) { width:21%; }
    .client-table th:nth-child(6) { width:13%; }
    .client-table td { vertical-align:top; overflow-wrap:anywhere; padding:.9rem .6rem; }
    .client-table .badge { max-width:100%; white-space:normal; text-align:left; }
    .client-table .form-control,.client-table .form-select { min-width:0; max-width:100%; }
    .client-table .btn { white-space:normal; }
    .client-table summary { font-size:.9rem; }
    .client-edit-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; }
    .client-edit-grid > div { min-width:0; }
    .client-edit-grid label { display:block; font-size:.75rem; color:#66736b; margin-bottom:.25rem; }
    .client-edit-save { grid-column:1 / -1; max-width:160px; }
    .client-table .client-management[hidden] { display:none; }
    .client-management { background:var(--owner-soft); }
    .client-management > td { padding:1rem; }
    .client-dates { display:flex; flex-wrap:wrap; gap:.5rem 2rem; margin-bottom:1rem; }
    .client-delete { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem .75rem; }
    .client-delete small { flex:1 1 180px; }
    @media (max-width:767.98px) {
        .client-table,.client-table tbody { display:block; }
        .client-table thead { display:none; }
        .client-table tr { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); padding:.75rem 0; border-bottom:1px solid var(--owner-line); }
        .client-table tr:last-child { border-bottom:0; }
        .client-table td { display:block; min-width:0; border:0; padding:.5rem; }
        .client-table td::before { content:attr(data-label); display:block; color:#66736b; font-size:.7rem; font-weight:700; text-transform:uppercase; margin-bottom:.35rem; }
        .client-table td:nth-child(2) { grid-column:span 2; }
        .client-table td:nth-child(5) { grid-column:span 3; }
        .client-table td:last-child { grid-column:1 / -1; }
        .client-table .client-management { grid-template-columns:minmax(0,1fr); }
        .client-table .client-management > td::before { display:none; }
        .client-edit-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:575.98px) {
        .client-table tr { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .client-table td:nth-child(1),.client-table td:nth-child(2),.client-table td:nth-child(5) { grid-column:1 / -1; }
        .client-edit-grid { grid-template-columns:minmax(0,1fr); }
    }
</style>
<div class="owner-card p-3">
    <div class="table-responsive">
        <table class="table owner-table client-table align-middle">
            <thead><tr><th>Client</th><th>Registered Emails</th><th>Businesses</th><th>Users</th><th>Subscription</th><th>Manage</th></tr></thead>
            <tbody>
            @forelse($tenants as $tenant)
                <tr>
                    <td data-label="Client">
                        <strong>{{ $tenant->name }}</strong>
                        <small class="d-block text-muted">{{ $tenant->industry ?: 'General' }} · {{ $tenant->primary_domain ?: $tenant->slug }}</small>
                    </td>
                    <td data-label="Registered emails">@include('platform.partials.registered-emails', ['tenant' => $tenant])</td>
                    <td data-label="Businesses">
                        @forelse($tenant->businesses as $business)
                            <span class="badge text-bg-light">{{ $business->name }}</span>
                        @empty
                            <span class="text-muted">None</span>
                        @endforelse
                    </td>
                    <td data-label="Users">{{ number_format($tenant->users_count) }}</td>
                    <td data-label="Subscription">
                        <strong>{{ $tenant->subscription?->plan?->name ?? 'No plan' }}</strong>
                        <small class="d-block text-muted">{{ $tenant->subscription?->status ?? 'none' }}</small>
                    </td>
                    <td data-label="Manage">
                        <button type="button" class="btn btn-outline-dark btn-sm client-toggle" aria-expanded="false" aria-controls="client-manage-{{ $tenant->id }}" aria-label="Expand {{ $tenant->name }}" data-client-name="{{ $tenant->name }}">+ Expand</button>
                    </td>
                </tr>
                <tr id="client-manage-{{ $tenant->id }}" class="client-management" hidden>
                    <td colspan="6">
                        <div class="client-dates small text-muted">
                            <span>Trial ends: {{ $tenant->subscription?->trial_ends_at?->format('d M Y H:i') ?? '—' }}</span>
                            <span>Renewal: {{ $tenant->subscription?->renews_at?->format('d M Y H:i') ?? '—' }}</span>
                        </div>
                        <details class="border rounded p-3 mb-3">
                            <summary class="fw-bold">Adjust subscription / restore access</summary>
                            <p class="small text-muted mt-2">Correct access after a transaction error. Reset starts a new period now; set an expiry to choose the exact end time. Dates use {{ config('app.timezone') }}. This adjustment does not record a payment or issue a receipt.</p>
                            <form method="post" action="{{ route('platform.tenants.subscription.update', $tenant) }}" class="row g-2">
                                @csrf @method('PUT')
                                <div class="col-md-6"><label class="form-label" for="adjust-action-{{ $tenant->id }}">Action</label><select id="adjust-action-{{ $tenant->id }}" name="action" class="form-select" required><option value="set_expiry">Set expiry date and restore access</option><option value="reset_monthly">Reset to 30 days from now</option><option value="reset_trial">Reset trial to 14 days from now</option></select></div>
                                <div class="col-md-6"><label class="form-label" for="adjust-plan-{{ $tenant->id }}">Package</label><select id="adjust-plan-{{ $tenant->id }}" name="plan_id" class="form-select" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($tenant->subscription?->plan_id === $plan->id)>{{ $plan->name }}</option>@endforeach</select></div>
                                <div class="col-12"><label class="form-label" for="adjust-expiry-{{ $tenant->id }}">Expiry date (required for set expiry only)</label><input id="adjust-expiry-{{ $tenant->id }}" type="datetime-local" name="expires_at" class="form-control"></div>
                                <div class="col-12"><label class="form-label" for="adjust-reason-{{ $tenant->id }}">Reason / transaction reference</label><textarea id="adjust-reason-{{ $tenant->id }}" name="reason" class="form-control" maxlength="1000" rows="2" required></textarea></div>
                                <div class="col-12"><button class="btn btn-owner">Apply subscription adjustment</button></div>
                            </form>
                            @foreach(array_reverse(data_get($tenant->subscription?->metadata, 'admin_adjustments', [])) as $adjustment)
                                <div class="small border-top mt-3 pt-2"><strong>{{ $adjustment['user_name'] }} (#{{ $adjustment['user_id'] }})</strong> · {{ $adjustment['at'] }}<br>{{ str($adjustment['action'])->headline() }} · Expires {{ $adjustment['expires_at'] }}<br>{{ $adjustment['reason'] }}</div>
                            @endforeach
                        </details>
                        <form method="post" action="{{ route('platform.tenants.update', $tenant) }}" class="client-edit-grid">
                            @csrf @method('PUT')
                            <div>
                                <label for="client-status-{{ $tenant->id }}">Client status</label>
                                <select id="client-status-{{ $tenant->id }}" class="form-select form-select-sm" name="status">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" @selected($tenant->status === $status)>{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="client-plan-{{ $tenant->id }}">Package</label>
                                <select id="client-plan-{{ $tenant->id }}" class="form-select form-select-sm" name="plan_id">
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected($tenant->subscription?->plan_id === $plan->id)>{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="client-subscription-{{ $tenant->id }}">Subscription status</label>
                                <select id="client-subscription-{{ $tenant->id }}" class="form-select form-select-sm" name="subscription_status">
                                    @foreach($subscriptionStatuses as $status)
                                        <option value="{{ $status }}" @selected(($tenant->subscription?->status ?? 'trialing') === $status)>{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div><label for="client-domain-{{ $tenant->id }}">Domain</label><input id="client-domain-{{ $tenant->id }}" class="form-control form-control-sm" name="primary_domain" value="{{ $tenant->primary_domain }}" placeholder="Domain"></div>
                            <div><label for="client-trial-{{ $tenant->id }}">Trial ends</label><input id="client-trial-{{ $tenant->id }}" class="form-control form-control-sm" type="date" name="trial_ends_at" value="{{ $tenant->trial_ends_at?->toDateString() }}"></div>
                            <div><label for="client-renews-{{ $tenant->id }}">Renews</label><input id="client-renews-{{ $tenant->id }}" class="form-control form-control-sm" type="date" name="renews_at" value="{{ $tenant->subscription?->renews_at?->toDateString() }}"></div>
                            <div class="client-edit-save"><button class="btn btn-owner btn-sm w-100"><i class="bi bi-save"></i> Save</button></div>
                        </form>
                        <form method="post" action="{{ route('platform.tenants.destroy', $tenant) }}" class="mt-3 client-delete" data-confirm-message="Delete {{ $tenant->name }}? This removes profile access, ends sessions, cancels the subscription, and hides its businesses." onsubmit="return confirm(this.dataset.confirmMessage);">
                            @csrf @method('DELETE')
                            <input type="hidden" name="confirm_delete" value="1">
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i> Delete profile
                            </button>
                            <small class="text-muted">Users remain only if they belong to another profile.</small>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No clients yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $tenants->links() }}
</div>
<script>
    document.querySelectorAll('.client-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const panel = document.getElementById(button.getAttribute('aria-controls'));
            const expanded = button.getAttribute('aria-expanded') === 'true';
            panel.hidden = expanded;
            button.setAttribute('aria-expanded', String(!expanded));
            button.textContent = expanded ? '+ Expand' : '− Minimize';
            button.setAttribute('aria-label', `${expanded ? 'Expand' : 'Minimize'} ${button.dataset.clientName}`);
        });
    });
</script>
@endsection
