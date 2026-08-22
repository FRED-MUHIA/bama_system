@extends('layouts.app')
@section('title', 'Hospitality Dashboard')

@section('content')
@include('hospitality.partials.nav')
<style>
    .hospitality-hero{background:#171717;color:#fff;border-radius:14px;padding:24px;border:1px solid rgba(0,166,81,.35)}
    .hospitality-badge{background:#eaf8f0;color:#007a3b;border:1px solid rgba(0,166,81,.22);border-radius:999px;padding:.35rem .65rem;font-size:.72rem;font-weight:800;text-decoration:none}
    .hospitality-badge:hover{background:#00A651;color:#fff;border-color:#00A651}
    .hospitality-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
    .hospitality-card{background:#fffdfa;border:1px solid #dedbd5;border-radius:10px;padding:16px;min-height:96px}
    .hospitality-value{font-size:1.35rem;font-weight:900;color:#171717}
    .room-board{display:grid;grid-template-columns:repeat(6,minmax(160px,1fr));gap:10px;overflow-x:auto}
    .room-column{background:#faf8f4;border:1px solid #dedbd5;border-radius:10px;padding:10px;min-height:160px}
    .room-tile{background:#fff;border:1px solid #e3e0da;border-radius:8px;padding:10px;margin-top:8px;cursor:grab}
    @media(max-width:1200px){.hospitality-grid{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:768px){.hospitality-grid{grid-template-columns:repeat(2,1fr)}.hospitality-hero{padding:18px}}
</style>

<section class="hospitality-hero mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
        <div>
            <div class="text-uppercase small fw-bold text-success">Industry command center</div>
            <h2 class="mb-1">Hospitality - {{ $industryDashboard['sub_industry'] ?? 'Hospitality' }}</h2>
            <p class="text-white-50 mb-0">{{ $industryDashboard['summary'] ?? 'Hospitality operations dashboard.' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            @foreach($moduleBadges as $badge)
                <a class="hospitality-badge" href="{{ route($badge['route']) }}">{{ $badge['label'] }}</a>
            @endforeach
        </div>
    </div>
</section>

<section class="hospitality-grid mb-4">
    @foreach($metrics as $label => $value)
        <div class="hospitality-card">
            <div class="text-muted small fw-bold text-uppercase">{{ $label }}</div>
            <div class="hospitality-value mt-2">{{ is_numeric($value) ? number_format($value, 2) : $value }}</div>
        </div>
    @endforeach
</section>

<section class="row g-3 mb-4">
    @foreach($kpis as $label => $value)
        <div class="col-md-3 col-sm-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">{{ $label }}</div>
                <div class="h4 mb-0 mt-2">{{ $value }}</div>
            </div></div>
        </div>
    @endforeach
</section>

<section class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Visual Room Board</span>
        <a class="btn btn-sm btn-outline-success" href="{{ route('hospitality.rooms.index') }}">Manage rooms</a>
    </div>
    <div class="card-body">
        <div class="room-board" id="room-board">
            @foreach(\Modules\Hospitality\Models\Room::STATUSES as $status)
                <div class="room-column" data-status="{{ $status }}">
                    <div class="fw-bold">{{ $status }}</div>
                    @foreach($rooms->where('status', $status) as $room)
                        <form method="post" action="{{ route('hospitality.rooms.status', $room) }}" class="room-tile" draggable="true" data-room-id="{{ $room->id }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <div class="fw-bold">Room {{ $room->room_number }}</div>
                            <div class="small text-muted">{{ $room->roomType?->name ?? 'Room' }} · {{ $room->floor ?: 'Floor n/a' }}</div>
                        </form>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100"><div class="card-header">Recent Reservations</div><div class="table-responsive">
            <table class="table align-middle"><thead><tr><th>No.</th><th>Guest</th><th>Stay</th><th>Status</th></tr></thead><tbody>
            @forelse($reservations as $reservation)
                <tr><td>{{ $reservation->reservation_number }}</td><td>{{ $reservation->guestProfile?->full_name ?? 'Guest' }}</td><td>{{ $reservation->arrival_date?->format('d M') }} - {{ $reservation->departure_date?->format('d M') }}</td><td><span class="status-pill">{{ $reservation->status }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="text-muted">No reservations yet.</td></tr>
            @endforelse
            </tbody></table>
        </div></div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100"><div class="card-header">Open Workflows</div><div class="table-responsive">
            <table class="table align-middle"><thead><tr><th>Area</th><th>Item</th><th>Status</th></tr></thead><tbody>
            @foreach($housekeeping as $task)
                <tr><td>Housekeeping</td><td>{{ $task->room?->room_number ? 'Room '.$task->room->room_number : $task->task_type }}</td><td><span class="status-pill">{{ $task->status }}</span></td></tr>
            @endforeach
            @foreach($maintenance as $request)
                <tr><td>Maintenance</td><td>{{ $request->title }}</td><td><span class="status-pill">{{ $request->priority }} / {{ $request->status }}</span></td></tr>
            @endforeach
            @if($housekeeping->isEmpty() && $maintenance->isEmpty())
                <tr><td colspan="3" class="text-muted">No open workflows.</td></tr>
            @endif
            </tbody></table>
        </div></div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let dragged = null;
    document.querySelectorAll('.room-tile').forEach((tile) => {
        tile.addEventListener('dragstart', () => dragged = tile);
    });
    document.querySelectorAll('.room-column').forEach((column) => {
        column.addEventListener('dragover', (event) => event.preventDefault());
        column.addEventListener('drop', () => {
            if (!dragged) return;
            dragged.querySelector('input[name="status"]').value = column.dataset.status;
            dragged.submit();
        });
    });
});
</script>
@endpush
@endsection
