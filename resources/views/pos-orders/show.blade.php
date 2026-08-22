@extends('layouts.app')
@section('title',$order->order_number)
@section('content')
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-outline-dark me-2" href="{{ route('pos-orders.edit',$order) }}"><i class="bi bi-pencil"></i> Edit</a>
    @if($order->status === 'pending')
        <form method="post" action="{{ route('pos-orders.approve',$order) }}" class="me-2">@csrf<button class="btn btn-warning"><i class="bi bi-check2-circle"></i> Approve</button></form>
    @endif
    <form method="post" action="{{ route('pos-orders.destroy',$order) }}" onsubmit="return confirm('Delete this POS order?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button></form>
</div>
<div class="row g-4">
    <div class="col-lg-8"><div class="card"><div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6"><h2 class="h5">Order</h2><p class="mb-1"><strong>{{ $order->order_number }}</strong></p><p class="mb-1">{{ $order->order_date?->format('d M Y') }}</p>@if($order->invoice)<p class="mb-1">Invoice: <a href="{{ route('invoices.show',$order->invoice) }}">{{ $order->invoice->invoice_number }}</a></p>@endif<p class="mb-1">Tracking: <a href="{{ route('public.orders.track',$order->tracking_key) }}" target="_blank">{{ $order->tracking_key }}</a></p><span class="status-pill">{{ $order->status }}</span>@if($order->approved_at)<div class="small text-muted mt-1">Approved {{ $order->approved_at->format('d M Y H:i') }}</div>@endif</div>
            <div class="col-md-6"><h2 class="h5">Customer</h2><p class="mb-1">{{ $order->client?->name ?: ($order->customer_name ?: 'Walk-in') }}</p><p class="mb-1">{{ $order->customer_type ?: 'Customer type not set' }}</p><p class="mb-1">{{ $order->customer_phone }}</p><p class="mb-1">{{ $order->client?->email ?: $order->customer_email }}</p><p class="text-muted">{{ $order->customer_address }}</p></div>
        </div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead><tbody>
        @foreach($order->items as $item)<tr><td>{{ $item->title }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price,2) }}</td><td>{{ number_format($item->line_total,2) }}</td></tr>@endforeach
        </tbody></table></div>
    </div></div></div>
    <div class="col-lg-4">
    <div class="card mb-4"><div class="card-body">
        <h2 class="h5">Totals</h2>
        <table class="table"><tr><th>Subtotal</th><td class="text-end">{{ number_format($order->subtotal,2) }}</td></tr><tr><th>Discount</th><td class="text-end">{{ number_format($order->discount_total,2) }}</td></tr><tr><th>Tax</th><td class="text-end">{{ number_format($order->tax_total,2) }}</td></tr><tr><th>Custom Amount</th><td class="text-end">{{ number_format($order->custom_amount,2) }}</td></tr><tr><th>Total</th><td class="text-end fw-bold">{{ number_format($order->total,2) }}</td></tr><tr><th>Paid</th><td class="text-end">{{ number_format($order->amount_paid,2) }}</td></tr><tr><th>Balance</th><td class="text-end">{{ number_format(max($order->total - $order->amount_paid, 0),2) }}</td></tr></table>
        <p class="mb-1"><strong>Payment:</strong> {{ $order->paymentMethod?->name ?: '-' }}</p>
        <p class="text-muted">{{ $order->notes }}</p>
    </div></div>
    <div class="card mb-4"><div class="card-body">
        <h2 class="h5">Record Payment</h2>
        @if(! $paymentsReady)
            <p class="text-muted mb-0">Payment recording will be available after the latest database migrations are run.</p>
        @elseif(max($order->total - $order->amount_paid, 0) > 0)
            <form method="post" action="{{ route('pos-orders.payments.store',$order) }}">
                @csrf
                <div class="mb-3"><label class="form-label">Amount</label><input class="form-control" type="number" step="0.01" name="amount" max="{{ max($order->total - $order->amount_paid, 0) }}" value="{{ old('amount', max($order->total - $order->amount_paid, 0)) }}" required></div>
                <div class="mb-3"><label class="form-label">Payment date</label><input class="form-control" type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required></div>
                <div class="mb-3"><label class="form-label">Payment method</label><select class="form-select" name="payment_method_id"><option value="">Select method</option>@foreach($methods as $method)<option value="{{ $method->id }}" @selected(old('payment_method_id', $order->payment_method_id)==$method->id)>{{ $method->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Reference</label><input class="form-control" name="reference" value="{{ old('reference') }}"></div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea></div>
                <button class="btn btn-warning w-100"><i class="bi bi-cash-coin"></i> Record Payment</button>
            </form>
        @else
            <p class="text-muted mb-0">This POS order is fully paid.</p>
        @endif
    </div></div>
    <div class="card"><div class="card-body">
        <h2 class="h5">Payment History</h2>
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>Date</th><th>Method</th><th>Ref</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @if($paymentsReady)
                    @forelse($order->payments as $payment)
                        <tr><td>{{ $payment->payment_date?->format('d M Y') }}</td><td>{{ $payment->paymentMethod?->name ?: '-' }}</td><td>{{ $payment->reference ?: '-' }}</td><td class="text-end">{{ number_format($payment->amount,2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No payment records yet.</td></tr>
                    @endforelse
                @else
                    <tr><td colspan="4" class="text-muted">Payment history will appear after migrations are run.</td></tr>
                @endif
            </tbody>
        </table></div>
    </div></div>
    </div>
</div>
@endsection
