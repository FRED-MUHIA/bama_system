@php
    $fitnessNav = [
        ['Dashboard', 'fitness.dashboard', 'fitness.dashboard', 'bi-speedometer2'],
        ['Memberships', 'fitness.memberships.index', 'fitness.memberships.*', 'bi-card-checklist'],
        ['Members', 'fitness.members.index', 'fitness.members.*', 'bi-people'],
        ['Trainers', 'fitness.trainers.index', 'fitness.trainers.*', 'bi-person-workspace'],
        ['Attendance', 'fitness.attendance.index', 'fitness.attendance.*', 'bi-qr-code-scan'],
        ['Check-In', 'fitness.check-in.index', 'fitness.check-in.*', 'bi-box-arrow-in-right'],
        ['Class Scheduling', 'fitness.classes.index', 'fitness.classes.*', 'bi-calendar-week'],
        ['Fitness Programs', 'fitness.programs.index', 'fitness.programs.*', 'bi-clipboard2-pulse'],
        ['Exercise Library', 'fitness.exercises.index', 'fitness.exercises.*', 'bi-list-check'],
        ['Health Profiles', 'fitness.health-profiles.index', 'fitness.health-profiles.*', 'bi-heart-pulse'],
        ['Assessments', 'fitness.assessments.index', 'fitness.assessments.*', 'bi-graph-up-arrow'],
        ['Personal Training', 'fitness.personal-training.index', 'fitness.personal-training.*', 'bi-person-arms-up'],
        ['Nutrition', 'fitness.nutrition.index', 'fitness.nutrition.*', 'bi-egg-fried'],
        ['Challenges', 'fitness.challenges.index', 'fitness.challenges.*', 'bi-trophy'],
        ['Equipment', 'fitness.equipment.index', 'fitness.equipment.*', 'bi-tools'],
        ['Reports', 'fitness.reports.index', 'fitness.reports.*', 'bi-bar-chart'],
    ];
@endphp

<nav class="nav nav-pills gap-2 mb-3 flex-wrap">
    @foreach($fitnessNav as [$label, $route, $match, $icon])
        <a class="nav-link {{ request()->routeIs($match) ? 'active' : '' }}" href="{{ route($route) }}">
            <i class="bi {{ $icon }} me-1"></i>{{ $label }}
        </a>
    @endforeach
    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box-seam me-1"></i>Inventory</a>
    <a class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}" href="{{ route('finance.index') }}"><i class="bi bi-cash-coin me-1"></i>Payments</a>
    <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}"><i class="bi bi-gear me-1"></i>Settings</a>
    <a class="nav-link {{ request()->routeIs('administration.*') ? 'active' : '' }}" href="{{ route('administration.index') }}"><i class="bi bi-shield-lock me-1"></i>Administration</a>
</nav>
