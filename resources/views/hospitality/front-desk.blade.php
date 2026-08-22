@extends('layouts.app')
@section('title', 'Hospitality Front Desk')

@section('content')
@include('hospitality.partials.nav')
<style>
    .frontdesk-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .frontdesk-card{background:#fffdfa;border:1px solid #dedbd5;border-radius:10px;padding:16px}
    .frontdesk-value{font-size:1.45rem;font-weight:900;color:#00A651}
    .frontdesk-workspace{display:grid;grid-template-columns:minmax(320px,.8fr) minmax(0,1.2fr);gap:16px}
    .frontdesk-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .room-readiness{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
    .room-chip{border:1px solid #dedbd5;border-radius:10px;background:#fff;padding:10px}
    .room-chip strong{display:block}
    @media(max-width:1100px){.frontdesk-grid{grid-template-columns:repeat(2,1fr)}.frontdesk-workspace{grid-template-columns:1fr}}
    @media(max-width:640px){.frontdesk-grid,.frontdesk-form-grid{grid-template-columns:1fr}}
</style>

<section class="frontdesk-grid mb-3">
    @foreach($metrics as $label => $value)
        <div class="frontdesk-card">
            <div class="text-muted small fw-bold text-uppercase">{{ $label }}</div>
            <div class="frontdesk-value">{{ number_format($value) }}</div>
        </div>
    @endforeach
</section>

<section class="frontdesk-workspace mb-3">
    <div class="frontdesk-card">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Quick Check-In</h2>
            <a class="btn btn-sm btn-outline-success" href="{{ route('hospitality.check-ins.index') }}">Full Check-In</a>
        </div>
        <form method="post" action="{{ route('hospitality.check-ins.store') }}" class="frontdesk-form-grid">
            @csrf
            <select class="form-select" name="reservation_id">
                <option value="">Reservation</option>
                @foreach($arrivals as $reservation)
                    <option value="{{ $reservation->id }}">{{ $reservation->reservation_number }} - {{ $reservation->guestProfile?->full_name ?? $reservation->client?->name }}</option>
                @endforeach
            </select>
            <select class="form-select" name="client_id">
                <option value="">Client / walk-in</option>
                @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
            </select>
            <select class="form-select" name="room_id" required>
                <option value="">Assign room</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}">Room {{ $room->room_number }} - {{ $room->roomType?->name ?? 'Room' }} - {{ $room->status }}</option>
                @endforeach
            </select>
            <input class="form-control" name="arrival_date" type="date" value="{{ now()->toDateString() }}">
            <input class="form-control" name="departure_date" type="date" value="{{ now()->addDay()->toDateString() }}">
            <input class="form-control" name="access_code" placeholder="Access code">
            <input class="form-control" name="deposit_amount" type="number" min="0" step="0.01" placeholder="Deposit">
            <input class="form-control" name="notes" placeholder="Notes">
            <button class="btn btn-success">Check In</button>
        </form>
    </div>

    <div class="frontdesk-card">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Quick Check-Out</h2>
            <a class="btn btn-sm btn-outline-success" href="{{ route('hospitality.check-outs.index') }}">Full Check-Out</a>
        </div>
        <form method="post" action="{{ route('hospitality.check-outs.store') }}" class="frontdesk-form-grid">
            @csrf
            <select class="form-select" name="reservation_id" required>
                <option value="">In-house reservation</option>
                @foreach($inHouse as $reservation)
                    <option value="{{ $reservation->id }}">{{ $reservation->reservation_number }} - {{ $reservation->guestProfile?->full_name ?? $reservation->client?->name }} - Room {{ $reservation->room?->room_number }}</option>
                @endforeach
            </select>
            <input class="form-control" name="restaurant_charges" type="number" min="0" step="0.01" placeholder="Restaurant charges">
            <input class="form-control" name="event_charges" type="number" min="0" step="0.01" placeholder="Event charges">
            <input class="form-control" name="other_charges" type="number" min="0" step="0.01" placeholder="Other services">
            <input class="form-control" name="payment_amount" type="number" min="0" step="0.01" placeholder="Payment collected">
            <input class="form-control" name="payment_method" placeholder="Payment method">
            <input class="form-control" name="payment_reference" placeholder="Payment reference">
            <input class="form-control" name="notes" placeholder="Notes">
            <button class="btn btn-success">Check Out</button>
        </form>
    </div>
</section>

<section class="row g-3 mb-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Arrivals</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Guest</th><th>Stay</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($arrivals as $reservation)
                        <tr><td>{{ $reservation->guestProfile?->full_name ?? $reservation->client?->name ?? 'Guest' }}<div class="small text-muted">{{ $reservation->reservation_number }}</div></td><td>{{ $reservation->arrival_date?->format('d M') }} - {{ $reservation->departure_date?->format('d M') }}</td><td><span class="status-pill">{{ $reservation->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No arrivals pending.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">In House</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Guest</th><th>Room</th><th>Due Out</th></tr></thead>
                    <tbody>
                    @forelse($inHouse as $reservation)
                        <tr><td>{{ $reservation->guestProfile?->full_name ?? $reservation->client?->name ?? 'Guest' }}<div class="small text-muted">{{ $reservation->reservation_number }}</div></td><td>{{ $reservation->room?->room_number ?? 'n/a' }}</td><td>{{ $reservation->departure_date?->format('d M Y') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No guests currently checked in.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Due Out</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Guest</th><th>Room</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($departures as $reservation)
                        <tr><td>{{ $reservation->guestProfile?->full_name ?? $reservation->client?->name ?? 'Guest' }}</td><td>{{ $reservation->room?->room_number ?? 'n/a' }}</td><td>{{ $reservation->departure_date?->format('d M Y') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No departures due.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Room Readiness</span>
                <a class="btn btn-sm btn-outline-success" href="{{ route('hospitality.rooms.index') }}">Manage Rooms</a>
            </div>
            <div class="card-body">
                <div class="room-readiness">
                    @foreach($rooms as $room)
                        <div class="room-chip">
                            <strong>Room {{ $room->room_number }}</strong>
                            <div class="small text-muted">{{ $room->roomType?->name ?? 'Room' }} · Floor {{ $room->floor ?: 'n/a' }}</div>
                            <span class="status-pill mt-2">{{ $room->status }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Restaurant Room Charges</span>
                <a class="btn btn-sm btn-outline-success" href="{{ route('hospitality.restaurant.index') }}">Restaurant POS</a>
            </div>
            <div class="card-body">
                @forelse($roomCharges as $order)
                    <div class="border-top py-2">
                        <strong>{{ $order->posOrder?->order_number ?? 'Restaurant order' }}</strong>
                        <span class="float-end">{{ number_format($order->total, 2) }}</span>
                        <div class="small text-muted">{{ $order->guestProfile?->full_name ?? $order->reservation?->guestProfile?->full_name ?? 'Guest' }}</div>
                    </div>
                @empty
                    <div class="text-muted">No room charges pending.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
