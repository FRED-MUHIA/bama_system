@extends('layouts.app')
@section('title', 'Retail Dashboard')

@section('content')
@include('retail.partials.nav')
<style>
    .retail-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .retail-metric{background:#fff;border:1px solid #d9dee8;border-radius:8px;padding:14px}
    .retail-metric .label{color:#667085;font-size:.72rem;font-weight:800;text-transform:uppercase}
    .retail-metric .value{font-size:1.45rem;font-weight:900;color:#0f766e}
    .retail-board{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    @media(max-width:1000px){.retail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.retail-board{grid-template-columns:1fr}}
    @media(max-width:640px){.retail-grid{grid-template-columns:1fr}}
</style>

<div class="retail-grid mb-3">
    @foreach($metrics as $label => $value)
        <div class="retail-metric">
            <div class="label">{{ $label }}</div>
            <div class="value">{{ is_numeric($value) ? number_format($value, str_contains($label, 'Sales') || str_contains($label, 'Revenue') || str_contains($label, 'Value') || str_contains($label, 'Basket') ? 2 : 0) : $value }}</div>
        </div>
    @endforeach
</div>

<div class="retail-board">
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Recent Transactions</h2>
            <a class="btn btn-sm btn-success" href="{{ route('retail.pos.index') }}">Open POS</a>
        </div>
        @forelse($recentOrders as $order)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $order->order_number }}</strong>
                    <div class="small text-muted">{{ $order->client?->name ?: $order->customer_name ?: 'Walk-in customer' }}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">{{ number_format($order->amount_paid, 2) }}</div>
                    <span class="status-pill">{{ ucfirst($order->status) }}</span>
                </div>
            </div>
        @empty
            <div class="text-muted">No retail transactions yet.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <h2 class="h5 mb-2">Top Products</h2>
        @forelse($topProducts as $product)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $product->title }}</strong>
                    <div class="small text-muted">{{ number_format($product->quantity, 3) }} units sold</div>
                </div>
                <div class="fw-bold">{{ number_format($product->total, 2) }}</div>
            </div>
        @empty
            <div class="text-muted">Sales mix appears after the first POS sale.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <h2 class="h5 mb-2">Top Cashiers</h2>
        @forelse($topCashiers as $cashier)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $cashier->name }}</strong>
                    <div class="small text-muted">{{ $cashier->transactions }} transactions</div>
                </div>
                <div class="fw-bold">{{ number_format($cashier->revenue, 2) }}</div>
            </div>
        @empty
            <div class="text-muted">Cashier performance appears once POS extensions are captured.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <h2 class="h5 mb-2">Shared ERP Services</h2>
        <div class="d-flex flex-wrap gap-2">
            <a class="status-pill text-decoration-none" href="{{ route('finance.index') }}">Finance</a>
            <a class="status-pill text-decoration-none" href="{{ route('accounting.index') }}">Accounting</a>
            <a class="status-pill text-decoration-none" href="{{ route('clients.index') }}">CRM</a>
            <a class="status-pill text-decoration-none" href="{{ route('products.index') }}">Inventory Core</a>
            <a class="status-pill text-decoration-none" href="{{ route('erp.reports') }}">Reporting</a>
            <a class="status-pill text-decoration-none" href="{{ route('administration.index') }}">RBAC</a>
        </div>
    </div>
</div>
@endsection
