@extends('layouts.app')
@section('title', $client->name.' - Retail Customer')

@section('content')
@include('retail.partials.nav')

@php
    $profile = $client->retailProfile;
    $loyalty = $client->retailLoyaltyAccount;
    $posOrderCount = (int) ($summary['pos_orders'] ?? 0);
    $retailOrderCount = (int) ($summary['retail_orders'] ?? 0);
    $posTotal = (float) ($summary['pos_total'] ?? 0);
    $posPaid = (float) ($summary['pos_paid'] ?? 0);
    $retailTotal = (float) ($summary['retail_total'] ?? 0);
    $totalSpend = $posTotal + $retailTotal;
    $preferences = collect($profile?->shopping_preferences ?? [])
        ->map(fn ($value, $key) => is_array($value) ? implode(', ', $value) : $value)
        ->filter();
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <a class="btn btn-sm btn-outline-dark mb-3" href="{{ route('retail.customers.index') }}"><i class="bi bi-arrow-left me-1"></i>Customers</a>
        <h1 class="h3 mb-1">{{ $client->name }}</h1>
        <div class="text-muted">{{ $client->company_name ?: 'Retail customer' }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <span class="status-pill">{{ $profile?->customer_segment ?? 'Retail Customer' }}</span>
        @if($loyalty)
            <span class="status-pill">{{ $loyalty->tier }} Loyalty</span>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Total Spend</div>
            <div class="h4 mb-0">{{ number_format($totalSpend, 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Orders</div>
            <div class="h4 mb-0">{{ number_format($posOrderCount + $retailOrderCount) }}</div>
            <div class="small text-muted">{{ $posOrderCount }} POS / {{ $retailOrderCount }} retail</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Paid Through POS</div>
            <div class="h4 mb-0">{{ number_format($posPaid, 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Loyalty Points</div>
            <div class="h4 mb-0">{{ number_format((int) ($loyalty?->points_balance ?? 0)) }}</div>
            <div class="small text-muted">{{ $loyalty?->status ?? 'Not enrolled' }}</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5 mb-3">Customer Details</h2>
            <div class="mb-2"><span class="text-muted small d-block">Phone</span>{{ $client->phone ?: '-' }}</div>
            <div class="mb-2"><span class="text-muted small d-block">Email</span>{{ $client->email ?: '-' }}</div>
            <div class="mb-2"><span class="text-muted small d-block">Address</span>{{ $client->address ?: '-' }}</div>
            <div class="mb-2"><span class="text-muted small d-block">KRA PIN</span>{{ $client->kra_pin ?: '-' }}</div>
            @if($client->notes)
                <div class="border-top pt-3 mt-3 text-muted">{{ $client->notes }}</div>
            @endif
        </div></div>

        <div class="card mb-4"><div class="card-body">
            <h2 class="h5 mb-3">Retail Profile</h2>
            <div class="mb-2"><span class="text-muted small d-block">Segment</span>{{ $profile?->customer_segment ?? 'Retail Customer' }}</div>
            <div class="mb-2"><span class="text-muted small d-block">Lifetime Value</span>{{ number_format((float) ($profile?->lifetime_value ?? $totalSpend), 2) }}</div>
            <div class="mb-2"><span class="text-muted small d-block">Recorded Purchases</span>{{ number_format((int) ($profile?->total_purchases ?? ($posOrderCount + $retailOrderCount))) }}</div>
            @if($preferences->isNotEmpty())
                <div class="mb-2">
                    <span class="text-muted small d-block">Preferences</span>
                    {{ $preferences->implode(', ') }}
                </div>
            @endif
            @if($profile?->customer_notes)
                <div class="border-top pt-3 mt-3 text-muted">{{ $profile->customer_notes }}</div>
            @endif
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Loyalty</h2>
            @if($loyalty)
                <div class="mb-2"><span class="text-muted small d-block">Number</span>{{ $loyalty->loyalty_number }}</div>
                <div class="mb-2"><span class="text-muted small d-block">Tier</span>{{ $loyalty->tier }}</div>
                <div class="mb-2"><span class="text-muted small d-block">Earned / Redeemed</span>{{ number_format((int) $loyalty->points_earned) }} / {{ number_format((int) $loyalty->points_redeemed) }}</div>
                <div class="mb-2"><span class="text-muted small d-block">Cashback</span>{{ number_format((float) $loyalty->cashback_balance, 2) }}</div>
                <div class="text-muted small">Joined {{ $loyalty->joined_at?->format('d M Y') ?: '-' }}</div>
            @else
                <p class="text-muted mb-0">No loyalty account has been created for this customer yet.</p>
            @endif
        </div></div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">POS Orders</h2>
                <span class="text-muted small">{{ number_format($posOrderCount) }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Order</th><th>Date</th><th>Status</th><th class="text-end">Paid</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse($posOrders as $order)
                        <tr>
                            <td><a class="fw-semibold text-decoration-none text-black" href="{{ route('pos-orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->order_date?->format('d M Y') ?: '-' }}</td>
                            <td><span class="status-pill">{{ $order->status }}</span></td>
                            <td class="text-end">{{ number_format((float) $order->amount_paid, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $order->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No POS orders yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pt-3">{{ $posOrders->links() }}</div>
        </div></div>

        <div class="card mb-4"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">Retail Orders</h2>
                <span class="text-muted small">{{ number_format($retailOrderCount) }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Order</th><th>Channel</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse($retailOrders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->order_number }}</strong>
                                @if($order->branch)
                                    <div class="small text-muted">{{ $order->branch->name }}</div>
                                @endif
                            </td>
                            <td>{{ $order->channel }}</td>
                            <td>{{ $order->order_date?->format('d M Y') ?: '-' }}</td>
                            <td><span class="status-pill">{{ $order->status }}</span></td>
                            <td class="text-end">{{ number_format((float) $order->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No retail orders yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pt-3">{{ $retailOrders->links() }}</div>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Personalized Offers</h2>
            @forelse($offers as $offer)
                <div class="d-flex flex-wrap justify-content-between gap-3 border-bottom py-3">
                    <div>
                        <div class="fw-semibold">{{ $offer->offer_name }}</div>
                        <div class="text-muted small">{{ $offer->offer_type }}{{ $offer->promotion ? ' from '.$offer->promotion->name : '' }}</div>
                        @if(data_get($offer->behavior_summary, 'total_spend') !== null)
                            <div class="small text-muted">Based on spend {{ number_format((float) data_get($offer->behavior_summary, 'total_spend'), 2) }}</div>
                        @endif
                    </div>
                    <div class="text-end">
                        <span class="status-pill">{{ $offer->status }}</span>
                        <div class="small text-muted mt-1">{{ $offer->valid_from?->format('d M Y') ?: '-' }} - {{ $offer->valid_until?->format('d M Y') ?: '-' }}</div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No personalized offers have been created for this customer yet.</p>
            @endforelse
        </div></div>
    </div>
</div>
@endsection
