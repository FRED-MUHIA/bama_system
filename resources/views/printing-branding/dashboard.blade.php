@extends('layouts.app')
@section('title', 'Printing & Branding')

@section('content')
<style>
    .print-shell{--print-surface:#fff;--print-surface-soft:#f8faf9;--print-border:#e2e8f0;--print-border-soft:#ecedf0;--print-ink:#101828;--print-muted:#667085;--print-kicker:#007a3b;--print-pill-bg:#e9fff2;--print-pill-ink:#007a3b;--print-button-bg:#101828;--print-button-hover:#1f2937;display:grid;gap:18px;color:var(--print-ink)}
    .print-hero{background:#050806;color:#fff;border-radius:16px;padding:26px;border:1px solid rgba(0,166,81,.28);box-shadow:0 22px 60px rgba(0,0,0,.12)}
    .print-kicker{color:var(--print-kicker);font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
    .print-title{font-size:clamp(2rem,4vw,3.8rem);line-height:1;margin:.35rem 0 .8rem;color:#fff}
    .print-title span{color:#00A651}
    .print-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .print-card{background:var(--print-surface);border:1px solid var(--print-border);border-radius:12px;padding:16px;color:var(--print-ink);box-shadow:0 12px 28px rgba(15,23,42,.05);transition:background-color .18s ease,border-color .18s ease,color .18s ease,box-shadow .18s ease}
    .print-card h2,.print-card h3,.print-card h4,.print-card h5,.print-card h6{color:var(--print-ink)}
    .print-label{color:var(--print-muted);font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
    .print-value{font-size:1.45rem;font-weight:900;color:var(--print-ink)}
    .print-board{display:grid;grid-template-columns:1.1fr .9fr;gap:16px}
    .print-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.28rem .65rem;background:var(--print-pill-bg);color:var(--print-pill-ink);font-size:.78rem;font-weight:800;transition:background-color .18s ease,color .18s ease}
    .print-list{display:grid;gap:10px}
    .print-item{display:flex;justify-content:space-between;gap:12px;border:1px solid var(--print-border-soft);border-radius:10px;padding:12px;background:var(--print-surface);color:var(--print-ink);transition:background-color .18s ease,border-color .18s ease,color .18s ease}
    .print-item strong,.print-item span{color:var(--print-ink)}
    .print-shell .text-muted{color:var(--print-muted)!important}
    .print-shell .btn-dark{background:var(--print-button-bg);border-color:var(--print-button-bg);color:#fff;transition:background-color .18s ease,border-color .18s ease,color .18s ease,transform .18s ease,box-shadow .18s ease}
    .print-shell .btn-dark:hover,.print-shell .btn-dark:focus{background:var(--print-button-hover);border-color:var(--print-button-hover);color:#fff;box-shadow:0 10px 22px rgba(16,24,40,.16)}
    html[data-theme="dark"] .print-shell{--print-surface:#1b1f1d;--print-surface-soft:#151816;--print-border:#313a35;--print-border-soft:#2a332e;--print-ink:#f4f7f2;--print-muted:#b7c0b9;--print-kicker:#71f0ad;--print-pill-bg:rgba(113,240,173,.14);--print-pill-ink:#93f6bf;--print-button-bg:#00A651;--print-button-hover:#17bf68}
    html[data-theme="dark"] .print-card{box-shadow:none}
    html[data-theme="dark"] .print-item{background:var(--print-surface-soft)}
    @media(max-width:1100px){.print-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.print-board{grid-template-columns:1fr}}
    @media(max-width:640px){.print-grid{grid-template-columns:1fr}.print-hero{padding:20px}}
</style>

<div class="print-shell">
    <section class="print-hero">
        <div class="print-kicker">Industry Workspace</div>
        <h1 class="print-title">Printing & Branding<br><span>{{ $industryDashboard['sub_industry'] ?? 'Operations' }}</span></h1>
        <p class="mb-0 text-white-50" style="max-width:780px">{{ $industryDashboard['summary'] ?? 'Estimating, artwork, proof approvals, job tickets, materials, machines, production, dispatch, costing, and reporting.' }}</p>
    </section>

    <section class="print-grid">
        @foreach($metrics as $label => $value)
            @continue($label === 'Waste Cost')
            <div class="print-card">
                <div class="print-label">{{ $label }}</div>
                <div class="print-value">{{ is_numeric($value) ? number_format((float) $value, str_contains($label, 'Revenue') || str_contains($label, 'Margin') ? 2 : 0) : $value }}</div>
            </div>
        @endforeach
    </section>

    <section class="print-board">
        <div class="print-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="print-kicker">Production Visibility</div>
                    <h2 class="h4 mb-0">Recent jobs</h2>
                </div>
                <a class="btn btn-sm btn-dark" href="{{ route('printing-branding.jobs') }}">Open Jobs</a>
            </div>
            <div class="print-list">
                @forelse($recentJobs as $job)
                    <div class="print-item">
                        <div>
                            <strong>{{ $job->job_number }}</strong>
                            <div class="text-muted small">{{ $job->client?->name ?? 'Client' }} · {{ $job->product_name }}</div>
                        </div>
                        <div class="text-end">
                            <span class="print-pill">{{ $job->status }}</span>
                            <div class="small text-muted mt-1">{{ $job->delivery_date?->format('d M Y') ?? 'No due date' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No production jobs yet.</div>
                @endforelse
            </div>
        </div>

        <div class="print-card">
            <div class="print-kicker">Operations Analytics</div>
            <h2 class="h4">Dashboard charts</h2>
            <div class="print-list mt-3">
                @foreach($charts as $label => $points)
                    <div class="print-item">
                        <strong>{{ $label }}</strong>
                        <span>{{ count($points) }} series</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="print-board">
        <div class="print-card">
            <h2 class="h5">Recent estimates</h2>
            <div class="print-list">
                @forelse($recentEstimates as $estimate)
                    <div class="print-item">
                        <div>
                            <strong>{{ $estimate->estimate_number }}</strong>
                            <div class="text-muted small">{{ $estimate->client?->name }} · {{ $estimate->product_name }}</div>
                        </div>
                        <strong>{{ number_format((float) $estimate->selling_price, 2) }}</strong>
                    </div>
                @empty
                    <div class="text-muted">Create estimates to start quoting print work.</div>
                @endforelse
            </div>
        </div>
        <div class="print-card">
            <h2 class="h5">Machines</h2>
            <div class="print-list">
                @forelse($machines as $machine)
                    <div class="print-item">
                        <div>
                            <strong>{{ $machine->name }}</strong>
                            <div class="text-muted small">{{ $machine->machine_type }} · {{ $machine->location ?: 'No location' }}</div>
                        </div>
                        <span class="print-pill">{{ $machine->status }}</span>
                    </div>
                @empty
                    <div class="text-muted">Add machines to schedule production.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
