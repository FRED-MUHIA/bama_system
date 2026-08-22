@extends('layouts.platform')
@section('title', 'Client Management')
@section('content')
<div class="owner-card p-3">
    <div class="table-responsive">
        <table class="table owner-table align-middle">
            <thead><tr><th>Client</th><th>Businesses</th><th>Users</th><th>Subscription</th><th>Update</th></tr></thead>
            <tbody>
            @forelse($tenants as $tenant)
                <tr>
                    <td>
                        <strong>{{ $tenant->name }}</strong>
                        <small class="d-block text-muted">{{ $tenant->industry ?: 'General' }} · {{ $tenant->primary_domain ?: $tenant->slug }}</small>
                    </td>
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
                    </td>
                    <td style="min-width:560px">
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
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No clients yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $tenants->links() }}
</div>
@endsection
