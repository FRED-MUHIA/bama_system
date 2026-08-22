@extends('layouts.app')
@section('title', 'Retail Analytics')

@section('content')
@include('retail.partials.nav')
@php($metrics = $service->metrics())
@php($profitability = $enterprise->skuProfitability())
<div class="card p-3">
    <h1 class="h4 mb-3">Retail Analytics</h1>
    <div class="row g-3">
        @foreach(['Gross Revenue', 'Net Revenue', 'Average Basket Value', 'Stock Value', 'Low Stock Alerts', 'Loyalty Members', 'VIP Customers'] as $label)
            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted fw-bold text-uppercase">{{ $label }}</div>
                    <div class="h4 mb-0">{{ number_format($metrics[$label] ?? 0, str_contains($label, 'Revenue') || str_contains($label, 'Value') || str_contains($label, 'Basket') ? 2 : 0) }}</div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="border-top mt-3 pt-3">
        <h2 class="h5 mb-2">SKU Profitability</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>SKU</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Margin</th></tr></thead>
                <tbody>
                    @forelse($profitability as $row)
                        @php($margin = (float) $row->revenue > 0 ? (((float) $row->profit / (float) $row->revenue) * 100) : 0)
                        <tr>
                            <td>{{ $row->sku }}</td>
                            <td>{{ number_format((float) $row->revenue, 2) }}</td>
                            <td>{{ number_format((float) $row->cost, 2) }}</td>
                            <td class="{{ (float) $row->profit < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $row->profit, 2) }}</td>
                            <td>{{ number_format($margin, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">SKU profitability appears after POS sales.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
