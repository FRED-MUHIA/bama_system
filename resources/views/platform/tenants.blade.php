@extends('layouts.platform')
@section('title', 'Client Management')
@section('content')
<div class="owner-card p-3">
    <div class="table-responsive">
        <table class="table owner-table align-middle">
            <thead><tr><th>Client</th><th>Registered Emails</th><th>Businesses</th><th>Users</th><th>Subscription</th><th>Manage</th></tr></thead>
            <tbody>
            @forelse($tenants as $tenant)
                <tr>
                    <td>
                        <strong>{{ $tenant->name }}</strong>
                        <small class="d-block text-muted">{{ $tenant->industry ?: 'General' }} · {{ $tenant->primary_domain ?: $tenant->slug }}</small>
                    </td>
                    <td>@include('platform.partials.registered-emails', ['tenant' => $tenant])</td>
                    <td>
                        @forelse($tenant->businesses as $business)
                            <span class="badge text-bg-light">{{ $business->name }}</span>
                        @empty
                            <span class="text-muted">None</span>
                        @endforelse
                    </td>
                    <td>{{ number_format($tenant->users_count) }}</td>
                    <td>
                        <strong>{{ $tenant->subscription?->plan?->name ?? 'No plan' }}</strong>
                        <small class="d-block text-muted">{{ $tenant->subscription?->status ?? 'none' }}</small>
                        <small class="d-block text-muted">Trial ends: {{ $tenant->subscription?->trial_ends_at?->format('d M Y H:i') ?? '—' }}</small>
                        <small class="d-block text-muted">Renewal: {{ $tenant->subscription?->renews_at?->format('d M Y H:i') ?? '—' }}</small>
                    </td>
                    <td style="min-width:560px">
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
                        <form method="post" action="{{ route('platform.tenants.update', $tenant) }}" class="row g-2">
                            @csrf @method('PUT')
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" name="status">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" @selected($tenant->status === $status)>{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" name="plan_id">
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected($tenant->subscription?->plan_id === $plan->id)>{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" name="subscription_status">
                                    @foreach($subscriptionStatuses as $status)
                                        <option value="{{ $status }}" @selected(($tenant->subscription?->status ?? 'trialing') === $status)>{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><input class="form-control form-control-sm" name="primary_domain" value="{{ $tenant->primary_domain }}" placeholder="Domain"></div>
                            <div class="col-md-4"><input class="form-control form-control-sm" type="date" name="trial_ends_at" value="{{ $tenant->trial_ends_at?->toDateString() }}" title="Trial ends"></div>
                            <div class="col-md-4"><input class="form-control form-control-sm" type="date" name="renews_at" value="{{ $tenant->subscription?->renews_at?->toDateString() }}" title="Renews"></div>
                            <div class="col-md-4"><button class="btn btn-owner btn-sm w-100"><i class="bi bi-save"></i> Save</button></div>
                        </form>
                        <form method="post" action="{{ route('platform.tenants.destroy', $tenant) }}" class="mt-2" data-confirm-message="Delete {{ $tenant->name }}? This removes profile access, ends sessions, cancels the subscription, and hides its businesses." onsubmit="return confirm(this.dataset.confirmMessage);">
                            @csrf @method('DELETE')
                            <input type="hidden" name="confirm_delete" value="1">
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i> Delete profile
                            </button>
                            <small class="text-muted ms-2">Users remain only if they belong to another profile.</small>
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
@endsection
