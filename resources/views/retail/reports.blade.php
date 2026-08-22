@extends('layouts.app')
@section('title', 'Retail Reports')

@section('content')
@include('retail.partials.nav')
@php($profitability = $enterprise->skuProfitability())
<div class="card p-3">
    <h1 class="h4 mb-3">Retail Reports</h1>
    <div class="row g-2">
        @foreach(['Daily Sales', 'Monthly Sales', 'Product Sales', 'Cashier Sales', 'Stock Levels', 'Reorder Reports', 'Safety Stock Forecast', 'Cycle Count Variance', 'Valuation Reports', 'Loyalty Reports', 'Purchase History', 'Customer Segments', 'Personalized Offers', 'Supplier Performance', 'Supplier Contracts', 'Purchase Reports', 'Landed Cost', 'RMA Restocking', 'BOPIS Fulfillment', 'Ship From Store', 'Branch Revenue', 'Branch Profitability', 'Branch Inventory', 'SKU Profitability', 'VAT/GST Jurisdictions', 'Audit Reporting'] as $report)
            <div class="col-md-4"><a class="btn btn-outline-dark w-100 text-start" href="{{ route('erp.reports') }}"><i class="bi bi-file-earmark-bar-graph me-1"></i>{{ $report }}</a></div>
        @endforeach
    </div>
    <div class="text-muted mt-3">Exports use the shared Reporting service formats: PDF, Excel, and CSV.</div>
    <div class="row g-3 border-top mt-3 pt-3">
        <div class="col-lg-7">
            <h2 class="h5 mb-2">Profitability by SKU</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>SKU</th><th>Revenue</th><th>Cost</th><th>Profit</th></tr></thead>
                    <tbody>
                        @forelse($profitability as $row)
                            <tr><td>{{ $row->sku }}</td><td>{{ number_format((float) $row->revenue, 2) }}</td><td>{{ number_format((float) $row->cost, 2) }}</td><td>{{ number_format((float) $row->profit, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No SKU profitability rows yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <h2 class="h5 mb-2">Tax & Currency Compliance</h2>
            @forelse($taxJurisdictions as $tax)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $tax->country }} {{ $tax->region ? '/ '.$tax->region : '' }} · {{ $tax->tax_name }}</span><strong>{{ number_format((float) $tax->tax_rate, 2) }}% {{ $tax->currency_code }}</strong></div>
            @empty
                <div class="text-muted">Map VAT/GST jurisdictions in Retail Settings.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
