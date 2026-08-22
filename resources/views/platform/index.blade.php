@extends('layouts.platform')
@section('title', 'Owner Overview')
@section('content')
<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Clients', 'value' => $metrics['tenants'], 'icon' => 'bi-buildings'],
        ['label' => 'Businesses', 'value' => $metrics['businesses'], 'icon' => 'bi-diagram-3'],
        ['label' => 'Users', 'value' => $metrics['users'], 'icon' => 'bi-people'],
        ['label' => 'Active Subscriptions', 'value' => $metrics['activeSubscriptions'], 'icon' => 'bi-check2-circle'],
    ] as $metric)
        <div class="col-md-6 col-xl-3">
            <div class="owner-card owner-metric">
                <i class="bi {{ $metric['icon'] }}"></i>
                <strong>{{ number_format($metric['value']) }}</strong>
                <span class="text-muted">{{ $metric['label'] }}</span>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="owner-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Recent Clients</h2>
                <a href="{{ route('platform.tenants') }}" class="btn btn-sm btn-owner"><i class="bi bi-arrow-right"></i> Open</a>
            </div>
            <div class="table-responsive">
                <table class="table owner-table align-middle mb-0">
                    <thead><tr><th>Name</th><th>Industry</th><th>Plan</th><th>Status</th><th>Businesses</th></tr></thead>
                    <tbody>
                    @forelse($tenants as $tenant)
                        <tr>
                            <td><strong>{{ $tenant->name }}</strong><small class="d-block text-muted">{{ $tenant->primary_domain ?: $tenant->slug }}</small></td>
                            <td>{{ $tenant->industry ?: 'General' }}</td>
                            <td>{{ $tenant->subscription?->plan?->name ?? 'No plan' }}</td>
                            <td><span class="badge badge-owner">{{ $tenant->status }}</span></td>
                            <td>{{ number_format($tenant->businesses_count) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No clients yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="owner-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Pricing</h2>
                <a href="{{ route('platform.plans') }}" class="btn btn-sm btn-owner"><i class="bi bi-pencil-square"></i> Edit</a>
            </div>
            <div class="d-grid gap-2">
                @foreach($plans as $plan)
                    <div class="d-flex justify-content-between align-items-center border rounded-2 p-2">
                        <div>
                            <strong>{{ $plan->name }}</strong>
                            <small class="d-block text-muted">{{ $plan->features_count }} limits</small>
                        </div>
                        <div class="text-end">
                            <strong>{{ $plan->currency }} {{ number_format($plan->monthly_price, 2) }}</strong>
                            <small class="d-block text-muted">{{ $plan->is_active ? 'Active' : 'Hidden' }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
