@extends('layouts.app')
@section('title', 'Automotive')

@section('content')
<style>
    .auto-shell{display:grid;gap:16px}
    .auto-hero{background:#050806;color:#fff;border-radius:14px;padding:24px;border:1px solid rgba(0,166,81,.3);box-shadow:0 18px 44px rgba(0,0,0,.12)}
    .auto-kicker{color:#83efb8;font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}
    .auto-title{font-size:clamp(2rem,4vw,3.4rem);line-height:1;margin:.35rem 0 .7rem}
    .auto-title span{color:#00A651}
    .auto-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .auto-card{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:16px;box-shadow:0 12px 28px rgba(15,23,42,.05)}
    .auto-label{color:#667085;font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em}
    .auto-value{font-size:1.28rem;font-weight:900;color:#050806}
    .auto-board{display:grid;grid-template-columns:1.05fr .95fr;gap:16px}
    .auto-list{display:grid;gap:9px}
    .auto-row{display:flex;justify-content:space-between;gap:12px;border:1px solid #edf0f4;border-radius:10px;padding:12px;background:#fff}
    .auto-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .62rem;background:#e9fff2;color:#007a3b;font-size:.75rem;font-weight:900}
    .auto-mods{display:flex;flex-wrap:wrap;gap:8px}
    .auto-mods span,.auto-mods a{border:1px solid rgba(0,166,81,.25);border-radius:999px;background:#eafff2;color:#007a3b;padding:.32rem .7rem;font-weight:800;font-size:.78rem;text-decoration:none}
    .auto-mods a:hover{background:#00A651;color:#fff;border-color:#00A651}
    @media(max-width:1100px){.auto-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.auto-board{grid-template-columns:1fr}}
    @media(max-width:640px){.auto-grid{grid-template-columns:1fr}.auto-row{display:grid}.auto-hero{padding:18px}}
</style>

@php
    $moduleLinks = [
        'Dashboard' => ['automotive.dashboard'],
        'CRM' => ['clients.index'],
        'Vehicle Management' => ['automotive.vehicles'],
        'Garage Management' => ['automotive.workshop'],
        'Job Cards' => ['automotive.job-cards'],
        'Service Bookings' => ['automotive.bookings'],
        'Inspections' => ['automotive.inspections'],
        'Estimates' => ['automotive.estimates'],
        'Parts & Inventory' => ['automotive.parts'],
        'Procurement' => ['erp.procurement'],
        'Sales' => ['automotive.sales'],
        'Billing' => ['invoices.index'],
        'Quality Control' => ['automotive.quality'],
        'Warranty' => ['automotive.warranty'],
        'Fleet' => ['automotive.fleet'],
        'Reports' => ['automotive.reports'],
        'Communication' => ['communication.center'],
        'Workshop Bays' => ['automotive.workshop'],
        'Diagnostics' => ['automotive.workshop'],
        'Labour Operations' => ['automotive.labour-operations'],
        'Technicians' => ['automotive.technicians'],
        'Road Tests' => ['automotive.road-tests'],
        'Vehicle Release' => ['automotive.vehicle-release'],
        'Job Costing' => ['automotive.job-costing'],
        'Comebacks' => ['automotive.warranty'],
        'Tyres' => ['automotive.specialty', ['type' => 'tyres']],
        'Body & Paint' => ['automotive.specialty', ['type' => 'body-paint']],
        'Insurance Repairs' => ['automotive.specialty', ['type' => 'insurance-repairs']],
        'Vehicle Sales' => ['automotive.sales'],
        'Service Reminders' => ['automotive.service-reminders'],
        'Customer Feedback' => ['automotive.customer-service'],
    ];
@endphp

<div class="auto-shell">
    <section class="auto-hero">
        <div class="auto-kicker">Industry Workspace</div>
        <h1 class="auto-title">Automotive<br><span>{{ $industryDashboard['sub_industry'] ?? 'Operations' }}</span></h1>
        <p class="mb-3 text-white-50" style="max-width:860px">{{ $industryDashboard['summary'] ?? 'Automotive ERP for vehicles, bookings, inspections, job cards, workshop bays, parts, quality, invoices, release, warranty, fleet, and reporting.' }}</p>
        <div class="auto-mods">
            @foreach(($industryDashboard['modules'] ?? []) as $module)
                @php($target = $moduleLinks[$module] ?? null)
                @if($target && \Illuminate\Support\Facades\Route::has($target[0]))
                    <a href="{{ route($target[0], $target[1] ?? []) }}">{{ $module }}</a>
                @else
                    <span>{{ $module }}</span>
                @endif
            @endforeach
        </div>
    </section>

    <section class="auto-grid">
        @foreach($metrics as $label => $value)
            <div class="auto-card">
                <div class="auto-label">{{ $label }}</div>
                <div class="auto-value">{{ is_numeric($value) ? number_format((float) $value, str_contains($label, 'Revenue') || str_contains($label, 'Value') || str_contains($label, 'Invoices') ? 2 : 0) : $value }}</div>
            </div>
        @endforeach
    </section>

    <section class="auto-board">
        <div class="auto-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <div class="auto-kicker">Workshop Control</div>
                    <h2 class="h4 mb-0">Open job cards</h2>
                </div>
                <a class="btn btn-sm btn-dark" href="{{ route('automotive.job-cards') }}">Open Jobs</a>
            </div>
            <div class="auto-list">
                @forelse($jobs as $job)
                    <div class="auto-row">
                        <div>
                            <strong>{{ $job->job_number }}</strong>
                            <div class="text-muted small">{{ $job->vehicle?->registration_number ?? 'No reg' }} · {{ $job->client?->name ?? 'No customer' }}</div>
                        </div>
                        <div class="text-end">
                            <span class="auto-pill">{{ $job->status }}</span>
                            <div class="small text-muted mt-1">{{ $job->technician?->name ?? 'Unassigned' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No automotive job cards yet.</div>
                @endforelse
            </div>
        </div>

        <div class="auto-card">
            <div class="auto-kicker">Alerts</div>
            <h2 class="h4 mb-3">Items needing attention</h2>
            <div class="auto-list">
                @foreach($alerts as $label => $count)
                    <div class="auto-row"><strong>{{ $label }}</strong><span class="auto-pill">{{ $count }}</span></div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="auto-board">
        <div class="auto-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Today's bookings</h2>
                <a class="btn btn-sm btn-outline-dark" href="{{ route('automotive.bookings') }}">Bookings</a>
            </div>
            <div class="auto-list">
                @forelse($bookings as $booking)
                    <div class="auto-row">
                        <div><strong>{{ $booking->booking_number }}</strong><div class="small text-muted">{{ $booking->vehicle?->registration_number }} · {{ $booking->requested_service }}</div></div>
                        <span class="auto-pill">{{ $booking->status }}</span>
                    </div>
                @empty
                    <div class="text-muted">No bookings recorded.</div>
                @endforelse
            </div>
        </div>

        <div class="auto-card">
            <h2 class="h5">Charts</h2>
            @foreach($charts as $title => $rows)
                <div class="mb-3">
                    <div class="auto-label mb-1">{{ $title }}</div>
                    <div class="auto-list">
                        @forelse((array) $rows as $name => $value)
                            <div class="auto-row py-2"><span>{{ $name ?: 'Unspecified' }}</span><strong>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</strong></div>
                        @empty
                            <div class="text-muted small">No data yet.</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
