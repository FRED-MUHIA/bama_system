@extends('layouts.app')
@section('title', 'All Transactions')
@section('content')
@include('retail.partials.nav')
<h1 class="h3 mb-3">All Transactions</h1>
@include('retail.partials.transaction-search')
@if(request()->filled('q'))
    <a class="btn btn-sm btn-outline-secondary mb-3" href="{{ route('retail.transactions.index') }}">Clear search</a>
@endif
<div class="card p-3">
    <p class="text-muted">{{ number_format($orders->total()) }} transactions found</p>
    @forelse($orders as $order)
        <article class="d-flex flex-column flex-sm-row justify-content-between gap-2 border-bottom py-3">
            <div>
                <h2 class="h6 mb-1">{{ $order->order_number }}</h2>
                <div>{{ $order->client?->name ?: $order->customer_name ?: 'Walk-in customer' }}</div>
                <div class="small text-muted">{{ $order->order_date?->format('d M Y') }} · {{ $order->client?->phone ?: $order->customer_phone }}</div>
                @foreach($order->payments as $payment)
                    <div class="small">{{ $payment->paymentMethod?->name ?: 'Payment' }}: {{ number_format($payment->amount, 2) }} @if($payment->reference) · {{ $payment->reference }} @endif</div>
                @endforeach
            </div>
            <div>
                <div>Total: <strong>{{ number_format($order->total, 2) }}</strong></div>
                <div>Paid: {{ number_format($order->amount_paid, 2) }}</div>
                <span class="status-pill">{{ ucfirst($order->status) }}</span>
            </div>
        </article>
    @empty
        <p class="text-muted mb-0">No transactions found.</p>
    @endforelse
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
