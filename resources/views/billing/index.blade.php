@extends('layouts.app')
@section('title', 'BAMA Billing')

@section('content')
@php
    $subscription = $tenant->subscription;
    $enabled = fn (string $provider) => (bool) ($paymentSettings[$provider]->is_enabled ?? false);
    $mpesaSetting = $paymentSettings['mpesa'] ?? null;
    $invoicePayable = $invoice && $invoice->status !== 'paid' && (float) $invoice->total > 0;
@endphp

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="text-muted small fw-bold text-uppercase">BAMA package</div>
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
                            ?? data_get($latestMpesaPayment->callback_payload, 'Body.stkCallback.ResultDesc')
                            ?? data_get($latestMpesaPayment->callback_payload, 'ResponseDescription'))
                        : null;
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
                            <form method="post" action="{{ route('billing.invoices.mpesa', $invoice) }}" class="d-grid gap-2">
                                @csrf
                                <input class="form-control" type="tel" inputmode="tel" autocomplete="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="0700000000 or 254700000000" pattern="(?:\+254\d{9}|254\d{9}|0\d{9}|[17]\d{8})" maxlength="13" title="Enter 0700000000 or 254700000000" @disabled(! $enabled('mpesa') || ! $invoicePayable) required>
                                <div class="form-text">Any payer number: 0700000000 or 254700000000.</div>
                                @if($enabled('mpesa') && ($mpesaSetting?->mode ?? 'sandbox') === 'sandbox')
                                    <div class="small text-warning-emphasis">M-PESA is in sandbox mode. Switch to live keys to prompt a real phone.</div>
                                @endif
                                <button class="btn btn-warning" @disabled(! $enabled('mpesa') || ! $invoicePayable)><i class="bi bi-send"></i> Prompt Phone</button>
                            </form>
                            @if($latestMpesaPayment)
                                <div class="border-top mt-3 pt-3 small">
                                    <div class="d-flex justify-content-between gap-2 mb-1">
                                        <span class="text-muted">Latest STK</span>
                                        <span class="badge {{ $latestMpesaPayment->status === 'paid' ? 'text-bg-success' : ($latestMpesaPayment->status === 'failed' ? 'text-bg-danger' : 'text-bg-light') }}">{{ str($latestMpesaPayment->status)->headline() }}</span>
                                    </div>
                                    <div class="text-muted">Phone: {{ $latestMpesaPayment->phone }}</div>
                                    @if($latestMpesaResult)
                                        <div class="text-muted mt-1">{{ $latestMpesaResult }}</div>
                                    @endif
                                    @if($latestMpesaPayment->status === 'pending' && $latestMpesaPayment->checkout_request_id)
                                        <form method="post" action="{{ route('billing.payments.mpesa-status', $latestMpesaPayment) }}" class="mt-2">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-arrow-repeat"></i> Check STK Status</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                            @unless($enabled('mpesa'))<div class="small text-muted mt-2">M-PESA is not enabled by owner yet.</div>@endunless
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-2 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-paypal text-primary"></i><strong>PayPal</strong></div>
                            <form method="post" action="{{ route('billing.invoices.paypal', $invoice) }}">
                                @csrf
                                <button class="btn btn-outline-dark w-100" @disabled(! $enabled('paypal') || ! $invoicePayable)><i class="bi bi-box-arrow-up-right"></i> Pay with PayPal</button>
                            </form>
                            @unless($enabled('paypal'))<div class="small text-muted mt-2">PayPal is not enabled by owner yet.</div>@endunless
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-2 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-credit-card text-success"></i><strong>Card</strong></div>
                            <form method="post" action="{{ route('billing.invoices.card', $invoice) }}">
                                @csrf
                                <button class="btn btn-outline-dark w-100" @disabled(! $enabled('card') || ! $invoicePayable)><i class="bi bi-credit-card-2-front"></i> Pay by Card</button>
                            </form>
                            @unless($enabled('card'))<div class="small text-muted mt-2">Card checkout is not enabled by owner yet.</div>@endunless
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">Run database migrations to activate BAMA billing invoices.</div>
            @endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card p-4 mb-4">
            <h2 class="h5">Change package</h2>
            <p class="text-muted">Generate a new BAMA invoice for another monthly package.</p>
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
            <h2 class="h5">Recent BAMA invoices</h2>
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
@endsection
