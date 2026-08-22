@extends('layouts.app')
@section('title', 'Tax & ETIMS')

@section('content')
<style>
    .compliance-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .compliance-card{background:#fff;border:1px solid #d9dee8;border-radius:8px;padding:16px}
    .compliance-card span{display:block;color:#667085;font-size:.74rem;font-weight:800;text-transform:uppercase}
    .compliance-card strong{display:block;font-size:1.45rem;color:#111827;margin-top:4px}
    .status-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .6rem;font-size:.78rem;font-weight:800;background:#eef2f7;color:#344054}
    .status-pill.ok{background:#e9f8ef;color:#04763b}
    .status-pill.warn{background:#fff4e5;color:#a15c00}
    .status-pill.bad{background:#feeceb;color:#b42318}
    @media(max-width:992px){.compliance-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:640px){.compliance-grid{grid-template-columns:1fr}}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <div class="text-muted small text-uppercase fw-bold">Shared Compliance Module</div>
        <h1 class="h3 mb-1">Tax & ETIMS</h1>
        <p class="text-muted mb-0">Fiscal invoices, tax records, receipt validation, offline queues, retries, and audit-ready status tracking.</p>
    </div>
    @if(auth()->user()?->hasPermission('etims.retry'))
        <form method="post" action="{{ route('etims.retry') }}" class="d-flex gap-2">
            @csrf
            <input type="hidden" name="limit" value="50">
            <button class="btn btn-success"><i class="bi bi-arrow-repeat"></i> Retry Pending</button>
        </form>
    @endif
</div>

<div class="compliance-grid mb-3">
    @foreach([
        'Submitted Invoices' => $metrics['submitted_invoices'] ?? 0,
        'Pending Invoices' => $metrics['pending_invoices'] ?? 0,
        'Failed Submissions' => $metrics['failed_submissions'] ?? 0,
        'Credit Notes' => $metrics['credit_notes'] ?? 0,
        'Debit Notes' => $metrics['debit_notes'] ?? 0,
        'Compliance Rate' => number_format($metrics['compliance_rate'] ?? 100, 2).'%',
    ] as $label => $value)
        <div class="compliance-card"><span>{{ $label }}</span><strong>{{ is_numeric($value) ? number_format($value) : $value }}</strong></div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="compliance-card h-100">
            <h2 class="h5 mb-3">Tax Summary</h2>
            <div class="row g-2">
                @foreach($taxSummary as $label => $value)
                    <div class="col-sm-6">
                        <div class="border rounded p-3">
                            <span>{{ $label }}</span>
                            <strong>{{ is_numeric($value) ? number_format($value, 2) : $value }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="compliance-card h-100">
            <h2 class="h5 mb-3">Recent Tax Records</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Type</th><th>Period</th><th>Payable</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($taxes as $tax)
                        <tr>
                            <td>{{ $tax->tax_type }}</td>
                            <td>{{ $tax->period_start }} - {{ $tax->period_end }}</td>
                            <td>{{ number_format($tax->payable_amount, 2) }}</td>
                            <td><span class="status-pill">{{ $tax->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">Tax records from Finance appear here.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="compliance-card">
    <h2 class="h5 mb-3">ETIMS Submission Queue</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Document</th><th>Industry</th><th>Invoice</th><th>Receipt</th><th>Status</th><th>Submitted</th><th>Error</th></tr></thead>
            <tbody>
            @forelse($submissions as $submission)
                @php
                    $statusClass = match($submission->status) {
                        \Shared\Compliance\Etims\Models\EtimsSubmission::STATUS_VALIDATED,
                        \Shared\Compliance\Etims\Models\EtimsSubmission::STATUS_SUBMITTED => 'ok',
                        \Shared\Compliance\Etims\Models\EtimsSubmission::STATUS_FAILED => 'bad',
                        default => 'warn',
                    };
                @endphp
                <tr>
                    <td>{{ $submission->document_type }}</td>
                    <td>{{ $submission->industry ?: $industry ?: 'All' }}</td>
                    <td>{{ $submission->fiscal_invoice_number ?: '-' }}</td>
                    <td>{{ $submission->fiscal_receipt_number ?: '-' }}</td>
                    <td><span class="status-pill {{ $statusClass }}">{{ $submission->status }}</span></td>
                    <td>{{ $submission->submitted_at?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td class="text-muted">{{ \Illuminate\Support\Str::limit($submission->last_error ?: '-', 80) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">Fiscal invoice, receipt, credit note, debit note, and offline queue submissions appear here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
