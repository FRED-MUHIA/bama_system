@extends('layouts.platform')
@section('title', 'Pricing Plans')
@section('content')
<div class="row g-4">
    @foreach($plans as $plan)
        @php($limits = $plan->limits ?? [])
        <div class="col-xl-6">
            <form method="post" action="{{ route('platform.plans.update', $plan) }}" class="owner-card p-3 h-100">
                @csrf @method('PUT')
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
                        <label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="limits[api_access]" value="1" @checked((bool) ($limits['api_access'] ?? false))> API access</label>
                        <button class="btn btn-owner"><i class="bi bi-save"></i> Save</button>
                    </div>
                </div>
            </form>
        </div>
    @endforeach
</div>

@php
    $gatewayDefinitions = [
        'mpesa' => [
            'title' => 'M-PESA STK Push',
            'icon' => 'bi-phone',
            'public' => 'Consumer key',
            'secret' => 'Consumer secret',
            'fields' => [
                'shortcode' => 'PayBill / Till shortcode',
                'passkey' => 'STK passkey',
                'callback_url' => 'Callback URL',
                'transaction_type' => 'Transaction type',
            ],
            'defaults' => ['callback_url' => route('billing.mpesa.callback'), 'transaction_type' => 'CustomerPayBillOnline'],
        ],
        'paypal' => [
            'title' => 'PayPal Checkout',
            'icon' => 'bi-paypal',
            'public' => 'Client ID',
            'secret' => 'Client secret',
            'fields' => [],
            'defaults' => [],
        ],
        'card' => [
            'title' => 'Card Checkout',
            'icon' => 'bi-credit-card',
            'public' => 'Public key',
            'secret' => 'Secret key',
            'fields' => [
                'checkout_url_template' => 'Hosted checkout URL template',
            ],
            'defaults' => ['checkout_url_template' => 'https://checkout.example.com/pay?invoice={invoice}&amount={amount}&currency={currency}&tenant={tenant}'],
        ],
    ];
@endphp

<form method="post" action="{{ route('platform.payment-settings.update') }}" class="owner-card p-3 mt-4">
    @csrf @method('PUT')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1">Payment Integrations</h2>
            <p class="text-muted mb-0">Enable package payments by card, M-PESA STK Push, and PayPal.</p>
        </div>
        <button class="btn btn-owner"><i class="bi bi-save"></i> Save Integrations</button>
    </div>

    <div class="row g-3">
        @foreach($gatewayDefinitions as $provider => $definition)
            @php
                $setting = $paymentSettings[$provider] ?? null;
                $config = $setting?->config ?? [];
            @endphp
            <div class="col-xl-4">
                <div class="border rounded-2 p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h3 class="h6 mb-1"><i class="bi {{ $definition['icon'] }} text-success me-1"></i>{{ $definition['title'] }}</h3>
                            <small class="text-muted">{{ strtoupper($provider) }}</small>
                        </div>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="providers[{{ $provider }}][is_enabled]" value="1" @checked($setting?->is_enabled)>
                        </label>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small">Mode</label>
                            <select class="form-select form-select-sm" name="providers[{{ $provider }}][mode]">
                                <option value="sandbox" @selected(($setting?->mode ?? 'sandbox') === 'sandbox')>Sandbox</option>
                                <option value="live" @selected(($setting?->mode ?? 'sandbox') === 'live')>Live</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ $definition['public'] }}</label>
                            <input class="form-control form-control-sm" name="providers[{{ $provider }}][public_key]" value="{{ $setting?->public_key }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ $definition['secret'] }}</label>
                            <input class="form-control form-control-sm" type="password" name="providers[{{ $provider }}][secret_key]" placeholder="{{ $setting?->secret_key ? 'Saved - leave blank to keep' : '' }}" autocomplete="new-password">
                        </div>
                        @foreach($definition['fields'] as $field => $label)
                            <div class="col-12">
                                <label class="form-label small">{{ $label }}</label>
                                <input class="form-control form-control-sm" name="providers[{{ $provider }}][config][{{ $field }}]" value="{{ old("providers.$provider.config.$field", $config[$field] ?? $definition['defaults'][$field] ?? '') }}" placeholder="{{ $definition['defaults'][$field] ?? '' }}">
                            </div>
                        @endforeach
                        <div class="col-12">
                            <label class="form-label small">Owner notes</label>
                            <textarea class="form-control form-control-sm" name="providers[{{ $provider }}][instructions]" rows="2">{{ $setting?->instructions }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</form>
@endsection
