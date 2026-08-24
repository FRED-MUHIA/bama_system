@extends('layouts.platform')
@section('title', 'Payment Integrations')

@section('content')
@php
    $gatewayDefinitions = [
        'mpesa' => [
            'title' => 'M-PESA STK Push',
            'icon' => 'bi-phone',
            'summary' => 'Prompt clients on their phone when they renew a BAMA package.',
            'public' => 'Daraja consumer key',
            'secret' => 'Daraja consumer secret',
            'help' => 'Use Safaricom Daraja app credentials. The callback URL must be reachable on HTTPS.',
            'fields' => [
                'shortcode' => ['label' => 'PayBill / Till shortcode', 'hint' => 'The receiving BAMA shortcode.'],
                'passkey' => ['label' => 'STK passkey', 'hint' => 'Daraja Lipa Na M-PESA online passkey.'],
                'callback_url' => ['label' => 'Callback URL', 'hint' => 'Paste this in Daraja if a callback URL is required.'],
                'transaction_type' => ['label' => 'Transaction type', 'hint' => 'Usually CustomerPayBillOnline.'],
            ],
            'defaults' => [
                'callback_url' => route('billing.mpesa.callback'),
                'transaction_type' => 'CustomerPayBillOnline',
            ],
        ],
        'paypal' => [
            'title' => 'PayPal Checkout',
            'icon' => 'bi-paypal',
            'summary' => 'Send clients to PayPal and capture the order after approval.',
            'public' => 'PayPal client ID',
            'secret' => 'PayPal client secret',
            'help' => 'Use REST app credentials from the PayPal developer dashboard.',
            'fields' => [],
            'defaults' => [],
        ],
        'card' => [
            'title' => 'Card Checkout',
            'icon' => 'bi-credit-card',
            'summary' => 'Connect a hosted card checkout page from your preferred card processor.',
            'public' => 'Public key',
            'secret' => 'Secret key',
            'help' => 'The URL can include {invoice}, {amount}, {currency}, and {tenant}.',
            'fields' => [
                'checkout_url_template' => ['label' => 'Hosted checkout URL template', 'hint' => 'Example: https://checkout.example.com/pay?invoice={invoice}&amount={amount}&currency={currency}&tenant={tenant}'],
            ],
            'defaults' => [
                'checkout_url_template' => 'https://checkout.example.com/pay?invoice={invoice}&amount={amount}&currency={currency}&tenant={tenant}',
            ],
        ],
    ];
@endphp

@unless($billingTablesReady)
    <div class="alert alert-warning">Run the billing migration before saving keys: <code>php artisan migrate --force</code></div>
@endunless

<form method="post" action="{{ route('platform.payment-settings.update') }}" class="d-grid gap-4">
    @csrf
    @method('PUT')

    <div class="owner-card p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h2 class="h5 mb-1">Package Payment Keys</h2>
                <p class="text-muted mb-0">Add the live or sandbox keys clients will use to pay BAMA package invoices.</p>
            </div>
            <button class="btn btn-owner" @disabled(! $billingTablesReady)><i class="bi bi-save"></i> Save Payment Keys</button>
        </div>
    </div>

    <div class="row g-4">
        @foreach($gatewayDefinitions as $provider => $definition)
            @php
                $setting = $paymentSettings[$provider] ?? null;
                $config = $setting?->config ?? [];
                $secretSaved = filled($setting?->secret_key);
            @endphp
            <div class="col-xl-4">
                <section class="owner-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $definition['icon'] }} text-success fs-4"></i>
                                <h2 class="h5 mb-0">{{ $definition['title'] }}</h2>
                            </div>
                            <p class="text-muted mt-2 mb-0">{{ $definition['summary'] }}</p>
                        </div>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="providers[{{ $provider }}][is_enabled]" value="1" @checked($setting?->is_enabled) @disabled(! $billingTablesReady)>
                        </label>
                    </div>

                    <div class="alert alert-light border small">{{ $definition['help'] }}</div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small">Environment</label>
                            <select class="form-select" name="providers[{{ $provider }}][mode]" @disabled(! $billingTablesReady)>
                                <option value="sandbox" @selected(($setting?->mode ?? 'sandbox') === 'sandbox')>Sandbox / Test</option>
                                <option value="live" @selected(($setting?->mode ?? 'sandbox') === 'live')>Live / Production</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">{{ $definition['public'] }}</label>
                            <input class="form-control" name="providers[{{ $provider }}][public_key]" value="{{ old("providers.$provider.public_key", $setting?->public_key) }}" @disabled(! $billingTablesReady)>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">{{ $definition['secret'] }}</label>
                            <input class="form-control" type="password" name="providers[{{ $provider }}][secret_key]" placeholder="{{ $secretSaved ? 'Saved - leave blank to keep current secret' : 'Paste secret key' }}" autocomplete="new-password" @disabled(! $billingTablesReady)>
                            @if($secretSaved)
                                <div class="form-text text-success"><i class="bi bi-check-circle"></i> Secret saved securely.</div>
                            @endif
                        </div>

                        @foreach($definition['fields'] as $field => $fieldMeta)
                            @php
                                $value = old("providers.$provider.config.$field", $config[$field] ?? $definition['defaults'][$field] ?? '');
                            @endphp
                            <div class="col-12">
                                <label class="form-label small">{{ $fieldMeta['label'] }}</label>
                                <input class="form-control" name="providers[{{ $provider }}][config][{{ $field }}]" value="{{ $value }}" placeholder="{{ $definition['defaults'][$field] ?? '' }}" @disabled(! $billingTablesReady)>
                                <div class="form-text">{{ $fieldMeta['hint'] }}</div>
                            </div>
                        @endforeach

                        <div class="col-12">
                            <label class="form-label small">Internal notes</label>
                            <textarea class="form-control" name="providers[{{ $provider }}][instructions]" rows="3" @disabled(! $billingTablesReady)>{{ old("providers.$provider.instructions", $setting?->instructions) }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
        @endforeach
    </div>
</form>
@endsection
