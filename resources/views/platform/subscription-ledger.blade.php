@extends('layouts.platform')
@section('title', 'Subscription payment records')
@section('content')
<p class="text-muted">Track subscription payments across all clients. Dates use {{ config('app.timezone') }}.</p>
<form method="get" action="{{ route('platform.subscription-payments') }}" class="owner-card p-3 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-lg-4"><label for="q" class="form-label">Client, email, invoice or transaction</label><input id="q" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" maxlength="200"></div>
        <div class="col-lg-2"><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-select"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
        <div class="col-lg-2"><label for="provider" class="form-label">Payment method</label><select id="provider" name="provider" class="form-select"><option value="">All methods</option>@foreach($providers as $provider)<option value="{{ $provider }}" @selected(($filters['provider'] ?? '') === $provider)>{{ strtoupper($provider) }}</option>@endforeach</select></div>
        <div class="col-lg-2"><label for="from" class="form-label">From</label><input id="from" type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="col-lg-2"><label for="to" class="form-label">To</label><input id="to" type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}"></div>
    </div>
    <div class="d-flex gap-2 mt-3"><button class="btn btn-owner">Filter records</button><a href="{{ route('platform.subscription-payments') }}" class="btn btn-outline-dark">Reset</a></div>
    <small class="text-muted d-block mt-2">Date filters use the payment date, or the creation date for unpaid attempts.</small>
</form>
<div class="owner-card p-3 mb-4">
    <h2 class="h5">Successful payments in this selection</h2>
    <div class="d-flex flex-wrap gap-4">
        @forelse($totals as $total)
            <div><strong class="fs-4">{{ $total->currency }} {{ number_format($total->total, 2) }}</strong><small class="d-block text-muted">{{ number_format($total->payment_count) }} payments</small></div>
        @empty
            <p class="text-muted mb-0">No successful payments match these filters.</p>
        @endforelse
    </div>
    <p class="small text-muted mt-2 mb-0">Totals include paid and successful transactions only. Refunded and partially refunded transactions are excluded; these are not net revenue totals.</p>
    <div class="d-flex flex-wrap gap-2 mt-3">@foreach($statusCounts as $status => $count)<span class="badge text-bg-light">{{ ucfirst(str_replace('_', ' ', $status)) }}: {{ number_format($count) }}</span>@endforeach</div>
</div>
<div class="owner-card p-3">
    <h2 class="h5 mb-3">Payment ledger <span class="text-muted">({{ number_format($payments->total()) }})</span></h2>
    <div class="table-responsive">
        <table class="table owner-table align-middle">
            <thead><tr><th>Date</th><th>Client / Invoice</th><th>Package</th><th>Amount</th><th>Method / Reference</th><th>Status / Tracking</th></tr></thead>
            <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="text-nowrap">{{ ($payment->paid_at ?: $payment->created_at)?->format('d M Y H:i') }}<small class="d-block text-muted">{{ $payment->paid_at ? 'Payment date' : 'Created' }} · #{{ $payment->id }}</small></td>
                    <td><strong>{{ $payment->invoice?->customer_name ?: $payment->tenant?->name ?: 'Unavailable client' }}</strong><small class="d-block">{{ $payment->invoice?->billing_email }}</small><small class="d-block text-muted">{{ $payment->invoice?->invoice_number ?? 'No invoice' }} · Client #{{ $payment->tenant_id }}</small></td>
                    <td>{{ data_get($payment->invoice?->metadata, 'plan_name') ?: $payment->invoice?->plan?->name ?: 'Unavailable package' }}</td>
                    <td class="text-nowrap fw-bold">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                    <td><strong>{{ strtoupper($payment->provider) }}</strong><small class="d-block text-break">{{ $payment->provider_receipt ?: $payment->provider_payment_id ?: $payment->merchant_reference ?: 'No reference yet' }}</small></td>
                    <td>
                        <span class="badge {{ $payment->isSuccessful() ? 'badge-owner' : 'text-bg-light' }}">{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</span>
                        <details class="small mt-2"><summary>Tracking details</summary>
                            <dl class="mb-0 mt-2">
                                <dt>Merchant reference</dt><dd class="text-break">{{ $payment->merchant_reference ?: '—' }}</dd>
                                <dt>Gateway transaction</dt><dd class="text-break">{{ $payment->provider_payment_id ?: '—' }}</dd>
                                <dt>Receipt</dt><dd>{{ $payment->provider_receipt ?: '—' }}</dd>
                                <dt>Created</dt><dd>{{ $payment->created_at?->format('d M Y H:i') }}</dd>
                                <dt>Paid</dt><dd>{{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}</dd>
                                <dt>Subscription processed</dt><dd>{{ $payment->processed_at?->format('d M Y H:i') ?? 'Not recorded' }}</dd>
                                @if($payment->failed_at)<dt>Failed</dt><dd>{{ $payment->failed_at->format('d M Y H:i') }}</dd>@endif
                                @if($payment->refunded_at)<dt>Refunded</dt><dd>{{ $payment->refunded_at->format('d M Y H:i') }}</dd>@endif
                                @if($payment->failure_message)<dt>Failure reason</dt><dd>{{ $payment->failure_message }}</dd>@endif
                            </dl>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No subscription payment records match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</div>
@endsection
