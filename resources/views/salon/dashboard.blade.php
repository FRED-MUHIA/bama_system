@extends('layouts.app')
@section('title', 'Salon & Spa')

@section('content')
<style>
    .salon-shell{display:grid;gap:18px}
    .salon-hero{background:#050806;color:#fff;border-radius:16px;padding:28px;border:1px solid rgba(0,166,81,.28);box-shadow:0 24px 70px rgba(0,0,0,.12)}
    .salon-kicker{color:#71f0ad;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
    .salon-title{font-size:clamp(2rem,4vw,4rem);line-height:.95;margin:.4rem 0 1rem}
    .salon-title span{color:#00A651}
    .salon-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:20px}
    .salon-actions a{border-radius:999px;padding:.75rem 1rem;text-decoration:none;font-weight:800}
    .salon-actions .primary{background:#00A651;color:#fff}
    .salon-actions .secondary{border:1px solid rgba(255,255,255,.28);color:#fff}
    .salon-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .salon-card{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:16px;box-shadow:0 12px 28px rgba(15,23,42,.05)}
    .salon-card .label{color:#667085;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
    .salon-card .value{font-size:1.55rem;font-weight:900;color:#050806}
    .salon-board{display:grid;grid-template-columns:1.2fr .8fr;gap:16px}
    .salon-list{display:grid;gap:10px}
    .salon-item{display:flex;justify-content:space-between;gap:14px;border:1px solid #ecedf0;border-radius:10px;padding:12px;background:#fff}
    .salon-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.28rem .65rem;background:#e9fff2;color:#007a3b;font-size:.78rem;font-weight:800}
    @media(max-width:1000px){.salon-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.salon-board{grid-template-columns:1fr}}
    @media(max-width:640px){.salon-metrics{grid-template-columns:1fr}.salon-hero{padding:22px}.salon-title{font-size:2.1rem}}
</style>

<div class="salon-shell">
    <section class="salon-hero">
        <div class="salon-kicker">Industry Workspace</div>
        <h1 class="salon-title">Salon & Spa<br><span>Operations</span></h1>
        <p class="mb-0 text-white-50" style="max-width:760px">Appointments, staff schedules, services, POS, memberships, loyalty, consultations, product usage, commissions, and wellness programs in one tenant-ready workspace.</p>
        <div class="salon-actions">
            <a class="primary" href="{{ route('salon.appointments.index') }}"><i class="bi bi-calendar-plus me-1"></i> Book Appointment</a>
            <a class="secondary" href="{{ route('salon.services.index') }}"><i class="bi bi-scissors me-1"></i> Manage Services</a>
            <a class="secondary" href="{{ route('salon.reports.index') }}"><i class="bi bi-bar-chart me-1"></i> Reports</a>
        </div>
    </section>

    <section class="salon-metrics">
        @foreach($metrics as $label => $value)
            <div class="salon-card">
                <div class="label">{{ $label }}</div>
                <div class="value">{{ is_numeric($value) ? number_format((float) $value, str_contains($label, 'Revenue') || str_contains($label, 'Commission') || str_contains($label, 'Consumption') || str_contains($label, 'Payments') ? 2 : 0) : $value }}</div>
            </div>
        @endforeach
    </section>

    <section class="salon-board">
        <div class="salon-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="salon-kicker">Live Bookings</div>
                    <h2 class="h4 mb-0">Upcoming appointments</h2>
                </div>
                <a class="btn btn-sm btn-dark" href="{{ route('salon.appointments.index') }}">Open</a>
            </div>
            <div class="salon-list">
                @forelse($appointments as $appointment)
                    <div class="salon-item">
                        <div>
                            <strong>{{ $appointment->profile?->client?->name ?? $appointment->client?->name ?? 'Walk-in client' }}</strong>
                            <div class="text-muted small">{{ $appointment->appointment_number }} · {{ $appointment->staff?->display_name ?? 'Unassigned' }}</div>
                        </div>
                        <div class="text-end">
                            <span class="salon-pill">{{ $appointment->status }}</span>
                            <div class="small text-muted mt-1">{{ $appointment->starts_at?->format('d M H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No upcoming appointments yet.</div>
                @endforelse
            </div>
        </div>

        <div class="salon-card">
            <div class="salon-kicker">Industry Dashboard</div>
            <h2 class="h4">Provisioned features</h2>
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach(($industryDashboard['dashboard_features'] ?? []) as $feature)
                    <span class="salon-pill">{{ $feature }}</span>
                @endforeach
            </div>
            <div class="salon-list">
                @foreach($kpis as $label => $value)
                    <div class="salon-item">
                        <strong>{{ $label }}</strong>
                        <span>{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="salon-board">
        <div class="salon-card">
            <h2 class="h5">Service catalogue</h2>
            <div class="salon-list">
                @forelse($services as $service)
                    @php
                        $hours = intdiv((int) $service->duration_minutes, 60);
                        $minutes = (int) $service->duration_minutes % 60;
                        $duration = trim(($hours ? $hours.'h ' : '').($minutes ? $minutes.'m' : '')) ?: '0m';
                    @endphp
                    <div class="salon-item">
                        <div>
                            <strong>{{ $service->name }}</strong>
                            <div class="text-muted small">{{ $service->category ?: 'General' }} · {{ $duration }}</div>
                        </div>
                        <strong>{{ number_format((float) $service->price, 2) }}</strong>
                    </div>
                @empty
                    <div class="text-muted">Create services to start booking.</div>
                @endforelse
            </div>
        </div>
        <div class="salon-card">
            <h2 class="h5">Staff capacity</h2>
            <div class="salon-list">
                @forelse($staff as $member)
                    <div class="salon-item">
                        <div>
                            <strong>{{ $member->display_name }}</strong>
                            <div class="text-muted small">{{ $member->role_title ?: 'Stylist / Therapist' }}</div>
                        </div>
                        <span class="salon-pill">{{ $member->commission_rate }}%</span>
                    </div>
                @empty
                    <div class="text-muted">Add staff profiles to plan schedules.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
