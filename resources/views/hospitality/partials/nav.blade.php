@php
    $navCompletion = [
        'hospitality.housekeeping.index' => ['completed' => 0, 'open' => 0],
        'hospitality.maintenance.index' => ['completed' => 0, 'open' => 0],
    ];

    if (\Illuminate\Support\Facades\Schema::hasTable('hospitality_housekeeping_tasks')) {
        $navCompletion['hospitality.housekeeping.index'] = [
            'completed' => \Modules\Hospitality\Models\HousekeepingTask::where('status', 'Completed')->count(),
            'open' => \Modules\Hospitality\Models\HousekeepingTask::where('status', '!=', 'Completed')->count(),
        ];
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('hospitality_maintenance_requests')) {
        $navCompletion['hospitality.maintenance.index'] = [
            'completed' => \Modules\Hospitality\Models\MaintenanceRequest::whereIn('status', ['Resolved', 'Closed'])->count(),
            'open' => \Modules\Hospitality\Models\MaintenanceRequest::whereNotIn('status', ['Resolved', 'Closed'])->count(),
        ];
    }

    $hospitalityMenu = [
        ['Dashboard', 'hospitality.dashboard', 'bi-speedometer2'],
        ['Reservations', 'hospitality.reservations.index', 'bi-calendar-check'],
        ['Rooms', 'hospitality.rooms.index', 'bi-door-open'],
        ['Stock', 'products.index', 'bi-box-seam'],
        ['Guests', 'hospitality.guests.index', 'bi-person-heart'],
        ['Staff', 'hospitality.staff.index', 'bi-people'],
        ['Suppliers', 'hospitality.suppliers.index', 'bi-truck'],
        ['Procurement', 'erp.procurement', 'bi-cart-check'],
        ['Front Desk', 'hospitality.front-desk.index', 'bi-building-check'],
        ['Check-In', 'hospitality.check-ins.index', 'bi-box-arrow-in-right'],
        ['Check-Out', 'hospitality.check-outs.index', 'bi-box-arrow-right'],
        ['Housekeeping', 'hospitality.housekeeping.index', 'bi-brush'],
        ['Maintenance', 'hospitality.maintenance.index', 'bi-tools'],
        ['Restaurant', 'hospitality.restaurant.index', 'bi-cup-hot'],
        ['Events', 'hospitality.events.index', 'bi-calendar-event'],
        ['Loyalty Program', 'hospitality.guests.index', 'bi-gem'],
        ['Reports', 'hospitality.reports.index', 'bi-bar-chart'],
    ];
@endphp
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($hospitalityMenu as [$label, $route, $icon])
        <a class="btn btn-sm {{ request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route) ? 'btn-success' : 'btn-outline-dark' }}" href="{{ route($route) }}">
            <i class="bi {{ $icon }}"></i> {{ $label }}
            @if(isset($navCompletion[$route]) && array_sum($navCompletion[$route]) > 0)
                @if($navCompletion[$route]['open'] === 0)
                    <span class="badge text-bg-light ms-1">Completed</span>
                @else
                    <span class="badge text-bg-light ms-1">{{ $navCompletion[$route]['completed'] }} completed</span>
                @endif
            @endif
        </a>
    @endforeach
</div>
