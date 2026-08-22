@extends('layouts.app')
@section('title', 'Construction')

@section('content')
<style>
    .con-shell{display:grid;gap:18px}
    .con-hero{background:#050806;color:#fff;border-radius:14px;padding:24px;border:1px solid rgba(0,166,81,.25);box-shadow:0 18px 44px rgba(0,0,0,.12)}
    .con-kicker{color:#83efb8;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
    .con-title{font-size:clamp(2rem,4vw,3.5rem);line-height:1;margin:.35rem 0 .7rem}
    .con-title span{color:#00A651}
    .con-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .con-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;box-shadow:0 10px 28px rgba(15,23,42,.05)}
    .con-label{color:#667085;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
    .con-value{font-size:1.35rem;font-weight:900;color:#050806}
    .con-board{display:grid;grid-template-columns:1.1fr .9fr;gap:16px}
    .con-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .62rem;background:#e9fff2;color:#007a3b;font-size:.75rem;font-weight:800}
    .con-item{display:flex;justify-content:space-between;gap:12px;border:1px solid #eef0f3;border-radius:9px;padding:11px;background:#fff}
    .con-list{display:grid;gap:9px}
    @media(max-width:1100px){.con-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.con-board{grid-template-columns:1fr}}
    @media(max-width:640px){.con-grid{grid-template-columns:1fr}.con-hero{padding:18px}}
</style>

<div class="con-shell">
    <section class="con-hero">
        <div class="con-kicker">Industry Workspace</div>
        <h1 class="con-title">Construction<br><span>{{ $industryDashboard['sub_industry'] ?? 'Operations' }}</span></h1>
        <p class="mb-0 text-white-50" style="max-width:780px">{{ $industryDashboard['summary'] ?? 'Construction ERP for BOQs, tenders, sites, materials, contractors, certifications, variations, quality, safety, and handover.' }}</p>
    </section>

    <section class="con-grid">
        @foreach($metrics as $label => $value)
            <div class="con-card">
                <div class="con-label">{{ $label }}</div>
                <div class="con-value">{{ is_numeric($value) ? number_format((float) $value, str_contains($label, 'Value') || str_contains($label, 'Cost') || str_contains($label, 'Amount') || str_contains($label, 'Budget') ? 2 : 0) : $value }}</div>
            </div>
        @endforeach
    </section>

    <section class="con-board">
        <div class="con-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="con-kicker">Project Control</div>
                    <h2 class="h4 mb-0">Active projects</h2>
                </div>
                <a class="btn btn-sm btn-dark" href="{{ route('construction.projects') }}">Open Projects</a>
            </div>
            <div class="con-list">
                @forelse($projects as $profile)
                    <div class="con-item">
                        <div>
                            <strong>{{ $profile->project_number }}</strong>
                            <div class="text-muted small">{{ $profile->project?->project_name }} · {{ $profile->client?->name ?? 'No client' }}</div>
                        </div>
                        <div class="text-end">
                            <span class="con-pill">{{ $profile->status }}</span>
                            <div class="small text-muted mt-1">{{ number_format((float) $profile->contract_value, 2) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No construction projects yet.</div>
                @endforelse
            </div>
        </div>
        <div class="con-card">
            <div class="con-kicker">Control Alerts</div>
            <h2 class="h4 mb-3">Items needing attention</h2>
            <div class="con-list">
                @foreach($alerts as $label => $count)
                    <div class="con-item"><strong>{{ $label }}</strong><span class="con-pill">{{ $count }}</span></div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="con-board">
        <div class="con-card">
            <h2 class="h5">Project profitability</h2>
            <div class="table-responsive"><table class="table">
                <thead><tr><th>Project</th><th>Contract</th><th>Certified</th><th>Cost</th><th>Margin</th></tr></thead>
                <tbody>
                    @forelse($profitability as $row)
                        <tr><td>{{ $row['project'] }}</td><td>{{ number_format($row['contract'], 2) }}</td><td>{{ number_format($row['certified'], 2) }}</td><td>{{ number_format($row['cost'], 2) }}</td><td>{{ $row['margin'] }}%</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No project profitability data yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="con-card">
            <h2 class="h5">Dashboard charts</h2>
            <div class="con-list">
                @foreach($charts as $label => $points)
                    <div class="con-item"><strong>{{ $label }}</strong><span>{{ count($points) }} series</span></div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
