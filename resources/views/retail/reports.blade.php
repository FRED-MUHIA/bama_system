@extends('layouts.app')
@section('title', 'Retail Reports')

@section('content')
@include('retail.partials.nav')
@php
    $enterprise = $enterprise ?? null;
    $primaryReports = $primaryReports ?? collect([
        ['slug' => 'daily-sales', 'label' => 'Daily Sales', 'icon' => 'bi-graph-up-arrow'],
        ['slug' => 'product-sales', 'label' => 'Product Sales', 'icon' => 'bi-bar-chart'],
        ['slug' => 'stock-levels', 'label' => 'Stock Levels', 'icon' => 'bi-box-seam'],
        ['slug' => 'returns', 'label' => 'Returns', 'icon' => 'bi-arrow-counterclockwise'],
    ]);
    $advancedReports = $advancedReports ?? collect([
        ['slug' => 'monthly-sales', 'label' => 'Monthly Sales', 'icon' => 'bi-calendar3'],
        ['slug' => 'cashier-sales', 'label' => 'Cashier Sales', 'icon' => 'bi-person-badge'],
        ['slug' => 'branch-revenue', 'label' => 'Branch Revenue', 'icon' => 'bi-diagram-3'],
        ['slug' => 'branch-profitability', 'label' => 'Branch Profitability', 'icon' => 'bi-graph-up-arrow'],
        ['slug' => 'sku-profitability', 'label' => 'SKU Profitability', 'icon' => 'bi-currency-dollar'],
        ['slug' => 'vat-gst-jurisdictions', 'label' => 'VAT/GST Jurisdictions', 'icon' => 'bi-receipt'],
    ]);
    $reportData = $report ?? ['title' => 'Retail Reports', 'subtitle' => 'Performance drawn from the active retail account.', 'metrics' => [], 'columns' => [], 'rows' => collect(), 'empty' => 'No rows yet for this report.'];
    $reportRows = collect($reportData['rows'] ?? []);
    $reportColumns = $reportData['columns'] ?? [];
    $selectedReportMeta = $selectedReportMeta ?? ['label' => 'Retail Reports'];
@endphp
<div class="card p-3">
    <h1 class="h4 mb-3">Retail Reports</h1>

    <div class="row g-2">
        @foreach($primaryReports as $reportItem)
            @php $reportSlug = is_array($reportItem) ? ($reportItem['slug'] ?? '') : Str::slug($reportItem); $reportLabel = is_array($reportItem) ? ($reportItem['label'] ?? ucfirst(str_replace('-', ' ', $reportSlug))) : $reportItem; $reportIcon = is_array($reportItem) ? ($reportItem['icon'] ?? 'bi-file-earmark-bar-graph') : 'bi-file-earmark-bar-graph'; @endphp
            <div class="col-md-4">
                <a class="btn w-100 text-start {{ ($selectedReport ?? 'daily-sales') === $reportSlug ? 'btn-dark text-white' : 'btn-outline-dark' }}" href="{{ route('retail.reports.index', ['report' => $reportSlug]) }}">
                    <i class="{{ $reportIcon }} me-1"></i>{{ $reportLabel }}
                </a>
            </div>
        @endforeach
    </div>

    <button id="retail-more-reports-toggle" class="btn btn-sm btn-outline-dark mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#advancedRetailReports" aria-expanded="false" aria-controls="advancedRetailReports">
        <i class="bi bi-three-dots me-1"></i>More reports
    </button>

    <div class="collapse border-top mt-3 pt-3" id="advancedRetailReports">
        <div class="row g-2">
            @foreach($advancedReports as $reportItem)
                @php $reportSlug = is_array($reportItem) ? ($reportItem['slug'] ?? '') : Str::slug($reportItem); $reportLabel = is_array($reportItem) ? ($reportItem['label'] ?? ucfirst(str_replace('-', ' ', $reportSlug))) : $reportItem; $reportIcon = is_array($reportItem) ? ($reportItem['icon'] ?? 'bi-file-earmark-bar-graph') : 'bi-file-earmark-bar-graph'; @endphp
                <div class="col-md-4">
                    <a class="btn w-100 text-start {{ ($selectedReport ?? 'daily-sales') === $reportSlug ? 'btn-dark text-white' : 'btn-outline-dark' }}" href="{{ route('retail.reports.index', ['report' => $reportSlug]) }}">
                        <i class="{{ $reportIcon }} me-1"></i>{{ $reportLabel }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 border-top pt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ $reportData['title'] ?? 'Retail Performance' }}</h2>
                <div class="text-muted small">{{ $reportData['subtitle'] ?? 'Live retail performance driven by current account activity.' }}</div>
            </div>
            <span class="badge bg-dark-subtle text-dark border">{{ $selectedReportMeta['label'] ?? 'Retail Report' }}</span>
        </div>

        @if(!empty($reportData['metrics']))
            <div class="row g-2 mb-3">
                @foreach($reportData['metrics'] as $metric)
                    <div class="col-md-3">
                        <div class="card border h-100">
                            <div class="card-body py-3">
                                <div class="small text-muted text-uppercase">{{ $metric['label'] ?? 'Metric' }}</div>
                                <div class="fw-semibold fs-5 mt-1">{{ $metric['value'] ?? '0' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($reportColumns) && !$reportRows->isEmpty())
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach($reportColumns as $key => $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportRows as $row)
                            <tr>
                                @foreach($reportColumns as $key => $label)
                                    <td>{{ $row[$key] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted py-3">{{ $reportData['empty'] ?? 'No rows yet for this report.' }}</div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('retail-more-reports-toggle');
        const panel = document.getElementById('advancedRetailReports');

        if (!toggle || !panel) return;

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const shouldOpen = !panel.classList.contains('show');
            panel.classList.toggle('show', shouldOpen);
            panel.classList.toggle('d-none', !shouldOpen);
            toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        });
    });
</script>
@endsection
