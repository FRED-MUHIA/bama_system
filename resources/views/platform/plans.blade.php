@extends('layouts.platform')
@section('title', 'Pricing Plans')
@section('content')
<div class="row g-4">
    @foreach($plans as $plan)
        @php
            $limits = $plan->limits ?? [];
            $planUpdateUrl = route('platform.plans.update', $plan);
            $apiAccessEnabled = (bool) ($limits['api_access'] ?? false);
        @endphp
        <div class="col-xl-6">
            <form method="post" action="{{ $planUpdateUrl }}" class="owner-card p-3 h-100">
                @csrf
                @method('PUT')
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">{{ $plan->name }}</h2>
                        <small class="text-muted">{{ $plan->slug }}</small>
                    </div>
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($plan->is_active)>
                    </label>
                </div>
                <div class="row g-2">
                    <div class="col-md-7"><input class="form-control" name="name" value="{{ $plan->name }}" required></div>
                    <div class="col-md-2"><input class="form-control" name="currency" value="{{ $plan->currency }}" maxlength="3" required></div>
                    <div class="col-md-3"><input class="form-control" type="number" step="0.01" min="0" name="monthly_price" value="{{ $plan->monthly_price }}" required></div>
                    <div class="col-md-3"><label class="form-label small">Users</label><input class="form-control" type="number" min="0" name="limits[users]" value="{{ data_get($limits, 'users') }}"></div>
                    <div class="col-md-3"><label class="form-label small">Storage MB</label><input class="form-control" type="number" min="0" name="limits[storage_mb]" value="{{ data_get($limits, 'storage_mb') }}"></div>
                    <div class="col-md-3"><label class="form-label small">Branches</label><input class="form-control" type="number" min="0" name="limits[branches]" value="{{ data_get($limits, 'branches') }}"></div>
                    <div class="col-md-3"><label class="form-label small">Projects</label><input class="form-control" type="number" min="0" name="limits[projects]" value="{{ data_get($limits, 'projects') }}"></div>
                    <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                        <label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="limits[api_access]" value="1" @checked($apiAccessEnabled)> API access</label>
                        <button class="btn btn-owner"><i class="bi bi-save"></i> Save</button>
                    </div>
                </div>
            </form>
        </div>
    @endforeach
</div>

<div class="owner-card p-3 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1">Payment Integrations</h2>
            <p class="text-muted mb-0">M-PESA, PayPal, and card keys now have their own setup page.</p>
        </div>
        <a class="btn btn-owner" href="{{ route('platform.payments') }}"><i class="bi bi-credit-card"></i> Open Payments</a>
    </div>
</div>
@endsection
