@extends('layouts.app')
@section('title', 'Fitness & Gym')

@section('content')
@include('fitness.partials.nav')
<style>
    .fitness-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .fitness-metric{background:#fffdfa;border:1px solid #dedbd5;border-radius:12px;padding:16px}
    .fitness-metric .label{color:#777;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
    .fitness-metric .value{font-size:1.7rem;font-weight:900;color:#00A651}
    .fitness-board{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    @media(max-width:900px){.fitness-grid,.fitness-board{grid-template-columns:1fr}}
</style>

<div class="fitness-grid mb-3">
    @foreach($metrics as $label => $value)
        <div class="fitness-metric">
            <div class="label">{{ $label }}</div>
            <div class="value">{{ is_numeric($value) ? number_format($value, str_contains($label, 'Revenue') || str_contains($label, 'Balances') ? 2 : 0) : $value }}</div>
        </div>
    @endforeach
</div>

<div class="fitness-board">
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Expiring This Week</h2>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('fitness.members.index') }}">Members</a>
        </div>
        @forelse($expiring as $membership)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $membership->member?->client?->name }}</strong>
                    <div class="small text-muted">{{ $membership->membership_number }} · {{ $membership->plan?->name }}</div>
                </div>
                <span class="status-pill">{{ $membership->ends_at?->format('d M') }}</span>
            </div>
        @empty
            <div class="text-muted">No memberships expiring this week.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Recent Members</h2>
            <a class="btn btn-sm btn-warning" href="{{ route('fitness.members.index') }}">Add Member</a>
        </div>
        @forelse($members as $member)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $member->client?->name }}</strong>
                    <div class="small text-muted">{{ $member->member_number }} · {{ $member->assignedTrainer?->name ?: 'No trainer' }}</div>
                </div>
                <span class="status-pill">{{ $member->status }}</span>
            </div>
        @empty
            <div class="text-muted">No members yet.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Membership Plans</h2>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('fitness.memberships.index') }}">Manage</a>
        </div>
        @forelse($plans as $plan)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $plan->name }}</strong>
                    <div class="small text-muted">{{ $plan->plan_type }} · {{ $plan->duration_days }} days</div>
                </div>
                <div class="fw-bold">{{ $plan->currency }} {{ number_format($plan->price, 2) }}</div>
            </div>
        @empty
            <div class="text-muted">Create your first membership plan.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <h2 class="h5 mb-2">Operations</h2>
        <div class="d-flex flex-wrap gap-2">
            @foreach([
                'Trainers' => 'fitness.trainers.index',
                'Attendance' => 'fitness.attendance.index',
                'Check-In' => 'fitness.check-in.index',
                'Class Scheduling' => 'fitness.classes.index',
                'Fitness Programs' => 'fitness.programs.index',
                'Exercise Library' => 'fitness.exercises.index',
                'Health Profiles' => 'fitness.health-profiles.index',
                'Assessments' => 'fitness.assessments.index',
                'Personal Training' => 'fitness.personal-training.index',
                'Nutrition' => 'fitness.nutrition.index',
                'Challenges' => 'fitness.challenges.index',
                'Equipment' => 'fitness.equipment.index',
                'Reports' => 'fitness.reports.index',
            ] as $label => $route)
                <a class="status-pill text-decoration-none" href="{{ route($route) }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
</div>
@endsection
