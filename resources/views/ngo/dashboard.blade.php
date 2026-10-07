@extends('layouts.app')
@section('title', 'NGO & Non-Profit Dashboard')
@section('content')
<style>
    .ngo-page{display:grid;gap:16px}.ngo-hero{border-radius:16px;padding:24px;background:linear-gradient(120deg,#06291a,#087a46);color:#fff}.ngo-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:12px}.ngo-card{border:1px solid #e5e9e7;border-radius:12px;background:#fff;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.04)}.ngo-kpi-label{font-size:.72rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:#667085}.ngo-kpi-value{font-size:1.45rem;font-weight:900;color:#0a3020}.ngo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.ngo-list{display:grid;gap:8px}.ngo-row{display:flex;justify-content:space-between;gap:12px;border:1px solid #edf0ee;border-radius:9px;padding:11px}.ngo-actions{display:flex;flex-wrap:wrap;gap:8px}@media(max-width:760px){.ngo-grid{grid-template-columns:1fr}.ngo-hero{padding:19px}}
</style>
<div class="ngo-page">
    <section class="ngo-hero">
        <div class="small text-uppercase fw-bold text-white-50">NGO &amp; Non-Profit Executive Workspace</div>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2">
            <div><h1 class="mb-1">{{ $tenant?->name ?? 'Organization' }}</h1><div class="text-white-50">Reporting period: {{ now()->startOfMonth()->format('d M Y') }} – {{ now()->endOfMonth()->format('d M Y') }} · Fiscal year {{ now()->year }}</div></div>
            <div class="ngo-actions"><a href="{{ route('ngo.programs.index') }}" class="btn btn-light fw-bold">Programs</a><a href="{{ route('ngo.activities.index') }}" class="btn btn-outline-light fw-bold">Plan activity</a><a href="{{ route('ngo.beneficiaries.index') }}" class="btn btn-outline-light fw-bold">Register beneficiary</a></div>
        </div>
    </section>
    <section class="ngo-kpis">
        @foreach($metrics as $label => $value)
            <article class="ngo-card"><div class="ngo-kpi-label">{{ $label }}</div><div class="ngo-kpi-value">{{ is_numeric($value) && (str_contains($label, 'Income') || str_contains($label, 'Expenses') || str_contains($label, 'Remaining') || str_contains($label, 'Balance')) ? number_format((float)$value, 2) : number_format((float)$value) }}</div></article>
        @endforeach
    </section>
    <section class="ngo-grid">
        <article class="ngo-card"><div class="d-flex justify-content-between align-items-center mb-3"><div><div class="ngo-kpi-label">Program delivery</div><h2 class="h5 mb-0">Programs</h2></div><a class="btn btn-sm btn-outline-success" href="{{ route('ngo.programs.index') }}">Manage</a></div>
            <div class="ngo-list">@forelse($programs as $program)<div class="ngo-row"><div><strong>{{ $program->name }}</strong><div class="small text-muted">{{ $program->sector?->name ?: 'Sector not set' }} · {{ $program->activities_count }} activities</div></div><span class="badge text-bg-light">{{ $program->status }}</span></div>@empty<div class="text-muted">Create your first program to start organizing interventions.</div>@endforelse</div>
        </article>
        <article class="ngo-card"><div class="ngo-kpi-label">Field calendar</div><h2 class="h5 mb-3">Upcoming activities</h2><div class="ngo-list">@forelse($upcomingActivities as $activity)<div class="ngo-row"><div><strong>{{ $activity->name }}</strong><div class="small text-muted">{{ $activity->program?->name ?: 'No program' }} · {{ $activity->location ?: 'Location not set' }}</div></div><span class="small text-nowrap">{{ $activity->starts_on?->format('d M Y') }}</span></div>@empty<div class="text-muted">No upcoming activities have been scheduled.</div>@endforelse</div></article>
    </section>
    <section class="ngo-card"><div class="d-flex justify-content-between align-items-center mb-3"><div><div class="ngo-kpi-label">Shared Bama Projects</div><h2 class="h5 mb-0">Projects</h2></div><a class="btn btn-sm btn-outline-success" href="{{ route('projects.index') }}">Open projects</a></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Project</th><th>Status</th><th>Last updated</th></tr></thead><tbody>@forelse($projects as $project)<tr><td>{{ $project->project_name }}</td><td>{{ $project->status }}</td><td>{{ $project->updated_at?->format('d M Y') }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No shared projects yet.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endsection
