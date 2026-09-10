@extends('layouts.app')
@section('title', 'Bama Billing')

@section('content')
@php
    $subscription = $tenant->subscription;
    $enabled = fn (string $provider) => (bool) ($paymentSettings[$provider]->is_enabled ?? false);
    $mpesaSetting = $paymentSettings['mpesa'] ?? null;
    $mpesaConfig = $mpesaSetting?->config ?? [];
    $mpesaCallbackUrl = data_get($mpesaConfig, 'callback_url')
        ?: config('services.mpesa.callback_url')
        ?: route('api.payments.mpesa.callback');
    $mpesaLive = $enabled('mpesa') && ($mpesaSetting?->mode ?? 'sandbox') === 'live';
    $mpesaConfigured = filled($mpesaSetting?->public_key ?: config('services.mpesa.consumer_key'))
        && filled($mpesaSetting?->secret_key ?: config('services.mpesa.consumer_secret'))
        && filled(data_get($mpesaConfig, 'shortcode', config('services.mpesa.shortcode')))
        && filled(data_get($mpesaConfig, 'passkey', config('services.mpesa.passkey')))
        && str_starts_with((string) $mpesaCallbackUrl, 'https://');
    $mpesaReady = $mpesaLive && $mpesaConfigured;
    $paypalSetting = $paymentSettings['paypal'] ?? null;
    $paypalConfigured = filled($paypalSetting?->public_key ?: config('services.paypal.client_id'))
        && filled($paypalSetting?->secret_key ?: config('services.paypal.secret'));
    $paypalReady = $enabled('paypal') && $paypalConfigured;
    $paypalKesUsdRate = (float) data_get($paypalSetting?->config ?? [], 'kes_usd_rate', 0);
    $paypalSupportedCurrencies = ['AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'THB', 'USD'];
    $invoiceCurrency = $invoice ? strtoupper($invoice->currency) : null;
    $paypalCurrencySupported = $invoice && (in_array($invoiceCurrency, $paypalSupportedCurrencies, true) || $invoiceCurrency === 'KES');
    $paypalConvertedUsd = $invoiceCurrency === 'KES' && $paypalKesUsdRate > 0 ? number_format(max(0.01, (float) $invoice->total / $paypalKesUsdRate), 2) : null;
    $invoicePayable = $invoice && $invoice->status !== 'paid' && (float) $invoice->total > 0;
    $mpesaResultMessage = function (?string $result): ?string {
        if (! $result) return null;
        $lower = strtolower($result);

        return match (true) {
            str_contains($lower, 'wrong credentials') => 'The payer entered the wrong M-PESA PIN or could not be authenticated. Send a new prompt and enter the correct PIN.',
            str_contains($lower, 'timeout') || str_contains($lower, 'cannot be reached') => 'The phone could not be reached or the STK prompt timed out. Confirm the phone has signal, then send a new prompt.',
            str_contains($lower, 'cancel') => 'The payer cancelled the M-PESA prompt. Send a new prompt to try again.',
            default => $result,
        };
    };
@endphp

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Bama package</div>
                    <h2 class="h4 mb-1">{{ $subscription?->plan?->name ?? 'No package' }}</h2>
                    <p class="text-muted mb-0">{{ $tenant->name }} subscription billing and renewal.</p>
                </div>
                <span class="badge {{ ($billingState['state'] ?? 'active') === 'locked' ? 'text-bg-danger' : 'text-bg-success' }}">
                    {{ str($billingState['state'] ?? 'active')->headline() }}
                </span>
            </div>

            @if(!empty($billingState['message']))
                <div class="alert {{ ($billingState['state'] ?? '') === 'locked' ? 'alert-danger' : 'alert-warning' }}">
                    <strong>{{ $billingState['message'] }}</strong>
                    @if(!empty($billingState['expires_at']))
                        <div class="small mt-1">Expires: {{ $billingState['expires_at']->format('d M Y') }} · Grace ends: {{ $billingState['grace_ends_at']?->format('d M Y') }}</div>
                    @endif
                </div>
            @endif

            @if($invoice)
                @php
                    $latestMpesaPayment = $invoice->payments->firstWhere('provider', 'mpesa');
                    $latestMpesaResult = $latestMpesaPayment
                        ? (data_get($latestMpesaPayment->callback_payload, 'stk_query.ResultDesc')
                            ?? data_get($latestMpesaPayment->callback_payload, 'callback.Body.stkCallback.ResultDesc')
                            ?? data_get($latestMpesaPayment->callback_payload, 'ResponseDescription'))
                        : null;
                    $latestMpesaResult = $mpesaResultMessage($latestMpesaResult);
                    $latestPaypalPayment = $invoice->payments->firstWhere('provider', 'paypal');
                    $reusablePaypalPayment = $latestPaypalPayment
                        && $latestPaypalPayment->status === 'requires_action'
                        && filled($latestPaypalPayment->payment_url)
                        && $latestPaypalPayment->created_at?->gte(now()->subHours(2));
                @endphp
                <div class="border rounded-2 p-3 mb-4">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Current invoice</div>
                            <h3 class="h5 mb-1">{{ $invoice->invoice_number }}</h3>
                            <div class="text-muted">{{ $invoice->plan?->name ?? 'Business package' }} · Due {{ $invoice->due_at?->format('d M Y') ?? 'now' }}</div>
                        </div>
                        <div class="text-end">
                            <div class="display-6 fw-bold">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</div>
                            <span class="badge {{ $invoice->status === 'paid' ? 'text-bg-success' : 'text-bg-light' }}">{{ str($invoice->status)->headline() }}</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-2 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-phone text-success"></i><strong>M-PESA STK</strong></div>
                            <form method="post" action="{{ route('billing.invoices.mpesa', $invoice) }}" class="d-grid gap-2" data-mpesa-form>
                                @csrf
                                <input class="form-control" type="tel" inputmode="tel" autocomplete="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="0700000000 or +254 700 000 000" pattern="[+0-9 ()-]{9,20}" maxlength="20" title="Enter 0700000000, 254700000000, or +254 700 000 000" data-mpesa-phone @disabled(! $mpesaReady || ! $invoicePayable) required>
                                <div class="form-text">Any payer number: 0700000000, 254700000000, or +254 700 000 000.</div>
                                @if($enabled('mpesa') && ! $mpesaLive)
                                    <div class="small text-warning-emphasis">M-PESA is in sandbox mode. Sandbox accepts test requests but does not prompt a real phone. Switch to live keys in the owner console.</div>
                                @endif
                                <button class="btn btn-warning" data-mpesa-submit @disabled(! $mpesaReady || ! $invoicePayable)><i class="bi bi-send"></i> Prompt Phone</button>
                            </form>
                            @if($latestMpesaPayment)
                                <div class="border-top mt-3 pt-3 small">
                                    <div class="d-flex justify-content-between gap-2 mb-1">
                                        <span class="text-muted">Latest STK</span>
                                        <span class="badge {{ $latestMpesaPayment->isSuccessful() ? 'text-bg-success' : (in_array($latestMpesaPayment->status, ['failed', 'cancelled', 'expired'], true) ? 'text-bg-danger' : 'text-bg-light') }}">{{ str($latestMpesaPayment->status)->headline() }}</span>
                                    </div>
                                    <div class="text-muted">Phone: {{ $latestMpesaPayment->maskedPhone() }}</div>
                                    @if($latestMpesaResult)
                                        <div class="text-muted mt-1">{{ $latestMpesaResult }}</div>
                                    @endif
                                    @if($latestMpesaPayment->status === 'pending' && $latestMpesaPayment->checkout_request_id)
                                        <form method="post" action="{{ route('billing.payments.mpesa-status', $latestMpesaPayment) }}" class="mt-2" data-mpesa-status-form>
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning w-100" data-mpesa-status-button><i class="bi bi-arrow-repeat"></i> Check Payment Status</button>
                                        </form>
                                        <div class="mt-2 text-muted" role="status" data-mpesa-status-output>Safaricom accepted the request. Waiting for handset delivery or a final result.</div>
                                    @endif
                                </div>
                            @endif
                            @unless($enabled('mpesa'))<div class="small text-muted mt-2">M-PESA is not enabled by owner yet.</div>@endunless
                            @if($enabled('mpesa') && $mpesaLive && ! $mpesaConfigured)<div class="small text-danger mt-2">M-PESA live credentials, shortcode, passkey, or HTTPS callback URL are incomplete.</div>@endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-2 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-paypal text-primary"></i><strong>PayPal</strong></div>
                            <form method="post" action="{{ route('billing.invoices.paypal', $invoice) }}" data-payment-form>
                                @csrf
                                <button class="btn btn-outline-dark w-100" data-payment-submit @disabled(! $paypalReady || ! $invoicePayable || ! $paypalCurrencySupported)><i class="bi bi-box-arrow-up-right"></i> {{ $reusablePaypalPayment ? 'Continue PayPal Checkout' : 'Pay with PayPal' }}</button>
                            </form>
                            @unless($enabled('paypal'))<div class="small text-muted mt-2">PayPal is not enabled by owner yet.</div>@endunless
                            @if($enabled('paypal') && ! $paypalConfigured)<div class="small text-danger mt-2">PayPal client ID or secret is missing in the owner payment settings.</div>@endif
                            @if($enabled('paypal') && $invoiceCurrency === 'KES' && $paypalConvertedUsd)
                                <div class="small text-muted mt-2">Charges about USD {{ $paypalConvertedUsd }} at KES {{ number_format($paypalKesUsdRate, 2) }} per USD.</div>
                            @elseif($enabled('paypal') && $invoiceCurrency === 'KES')
                                <div class="small text-muted mt-2">KES will be converted to USD using the live exchange rate.</div>
                            @elseif($enabled('paypal') && ! $paypalCurrencySupported)
                                <div class="small text-muted mt-2">PayPal conversion is not configured for {{ $invoiceCurrency }} invoices.</div>
                            @elseif($enabled('paypal'))
                                <div class="small text-muted mt-2">Continue with PayPal to approve your payment.</div>
                            @endif
                            @if($latestPaypalPayment)
                                <div class="border-top mt-3 pt-3 small">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="text-muted">Latest PayPal attempt</span>
                                        <span class="badge {{ $latestPaypalPayment->isSuccessful() ? 'text-bg-success' : (in_array($latestPaypalPayment->status, ['failed', 'cancelled', 'expired'], true) ? 'text-bg-danger' : 'text-bg-light') }}">{{ str($latestPaypalPayment->status)->headline() }}</span>
                                    </div>
                                    @if($latestPaypalPayment->failure_message)<div class="text-danger mt-1">{{ $latestPaypalPayment->failure_message }}</div>@endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-2 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-credit-card text-success"></i><strong>Card</strong></div>
                            <form method="post" action="{{ route('billing.invoices.card', $invoice) }}" data-payment-form>
                                @csrf
                                <button class="btn btn-outline-dark w-100" data-payment-submit @disabled(! $enabled('card') || ! $invoicePayable)><i class="bi bi-credit-card-2-front"></i> Pay by Card</button>
                            </form>
                            @unless($enabled('card'))<div class="small text-muted mt-2">Card checkout is not enabled by owner yet.</div>@endunless
                            @if($enabled('card'))<div class="small text-muted mt-2">Additional bank verification is handled securely by Stripe when required.</div>@endif
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">Run database migrations to activate Bama billing invoices.</div>
            @endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card p-4 mb-4">
            <h2 class="h5">Change package</h2>
            <p class="text-muted">Generate a new Bama invoice for another monthly package.</p>
            <form method="post" action="{{ route('billing.invoices.store') }}" class="d-grid gap-2">
                @csrf
                <select class="form-select" name="plan_id" required>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($subscription?->plan_id === $plan->id)>
                            {{ $plan->name }} - {{ (float) $plan->monthly_price > 0 ? $plan->currency.' '.number_format((float) $plan->monthly_price, 2) : 'Custom' }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-outline-warning"><i class="bi bi-receipt"></i> Create Invoice</button>
            </form>
        </div>

        <div class="card p-4">
            <h2 class="h5">Recent Bama invoices</h2>
            <div class="d-grid gap-2">
                @forelse($invoices as $item)
                    <div class="border rounded-2 p-2">
                        <div class="d-flex justify-content-between gap-2">
                            <strong>{{ $item->invoice_number }}</strong>
                            <span>{{ $item->currency }} {{ number_format((float) $item->total, 2) }}</span>
                        </div>
                        <div class="small text-muted">{{ $item->plan?->name ?? 'Package' }} · {{ str($item->status)->headline() }} · {{ $item->created_at->format('d M Y') }}</div>
                    </div>
                @empty
                    <div class="text-muted">No subscription invoices yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-mpesa-form]').forEach((form) => {
    const input = form.querySelector('[data-mpesa-phone]');
    const button = form.querySelector('[data-mpesa-submit]');
    const normalize = () => {
        let value = input.value.replace(/\D+/g, '');
        if (value.startsWith('2540')) value = '254' + value.slice(4);
        input.value = value;
    };

    input?.addEventListener('input', normalize);
    normalize();
    form.addEventListener('submit', () => {
        normalize();
        if (button) {
            button.disabled = true;
            button.textContent = 'Sending...';
        }
    });
});

document.querySelectorAll('[data-payment-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-payment-submit]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Connecting...';
        }
    });
});

document.querySelectorAll('[data-mpesa-status-form]').forEach((form) => {
    const button = form.querySelector('[data-mpesa-status-button]');
    const output = document.querySelector('[data-mpesa-status-output]');
    let attempts = 0;

    const checkStatus = async () => {
        if (button?.disabled) return { final: false };
        if (button) button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            const payload = await response.json();
            if (output) {
                output.textContent = payload.message || 'Unable to read the M-PESA status.';
                output.className = 'mt-2 ' + (payload.status === 'error' || ['failed', 'cancelled', 'expired'].includes(payload.status) ? 'text-danger' : 'text-muted');
            }
            if (payload.final) window.location.reload();

            return payload;
        } catch (error) {
            if (output) {
                output.textContent = 'Could not check M-PESA status. Tap the button to retry.';
                output.className = 'mt-2 text-danger';
            }

            return { final: false };
        } finally {
            if (button) button.disabled = false;
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        checkStatus();
    });

    const poll = async () => {
        if (document.visibilityState !== 'visible') return;
        attempts++;
        const result = await checkStatus();
        if (! result.final && attempts < 4) window.setTimeout(poll, 15000);
    };

    window.setTimeout(poll, 12000);
});
</script>
@endsection
