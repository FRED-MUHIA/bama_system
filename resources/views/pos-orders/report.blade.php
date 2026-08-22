@extends('layouts.app')
@section('title','POS Order Report')
@section('content')
<div class="card mb-4"><div class="card-body">
    <form class="row g-3 align-items-end" method="get" action="{{ route('pos-orders.report') }}">
        <div class="col-md-4"><label class="form-label">From</label><input class="form-control" type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
        <div class="col-md-4"><label class="form-label">To</label><input class="form-control" type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
        <div class="col-md-4"><button class="btn btn-warning">Generate Report</button></div>
    </form>
</div></div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Orders</div><div class="h4">{{ $orders->count() }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Collected Payments</div><div class="h4">{{ number_format($revenue,2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Pending Value</div><div class="h4">{{ number_format($pending,2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Units Sold</div><div class="h4">{{ number_format($unitsSold,2) }}</div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-7"><div class="card"><div class="card-body table-responsive">
        <h2 class="h5">Orders</h2>
        <table class="table align-middle"><thead><tr><th>Order</th><th>Tracking Key</th><th>Customer</th><th>Type</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>
        @forelse($orders as $order)<tr><td><a href="{{ route('pos-orders.show',$order) }}">{{ $order->order_number }}</a></td><td>{{ $order->tracking_key }}</td><td>{{ $order->client?->name ?: ($order->customer_name ?: 'Walk-in') }}</td><td>{{ $order->customer_type ?: '-' }}</td><td>{{ $order->order_date?->format('d M Y') }}</td><td>{{ number_format($order->total,2) }}</td><td><span class="status-pill">{{ $order->status }}</span></td></tr>@empty<tr><td colspan="7" class="text-muted">No orders in this period.</td></tr>@endforelse
        </tbody></table>
    </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-body table-responsive">
        <h2 class="h5">Top Products</h2>
        <table class="table align-middle"><thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead><tbody>
        @forelse($topProducts as $product)<tr><td>{{ $product['name'] }}</td><td>{{ number_format($product['qty'],2) }}</td><td>{{ number_format($product['total'],2) }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No product sales yet.</td></tr>@endforelse
        </tbody></table>
    </div></div></div>
</div>
@endsection
