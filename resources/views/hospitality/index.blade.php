@extends('layouts.app')
@section('title', 'Hospitality - '.$title)

@section('content')
@include('hospitality.partials.nav')
<style>
    .hospitality-panel{background:#fffdfa;border:1px solid #dedbd5;border-radius:12px}
    .hospitality-form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .restaurant-layout{display:grid;grid-template-columns:minmax(280px,360px) minmax(0,1fr);gap:16px}
    .menu-list{display:grid;gap:10px;max-height:460px;overflow:auto;padding-right:4px}
    .menu-row{display:grid;grid-template-columns:minmax(0,1fr) 86px;gap:10px;align-items:center;border:1px solid #dedbd5;border-radius:10px;padding:10px;background:#fff}
    .menu-price{font-weight:800;color:#00A651}
    .qty-input{max-width:86px}
    .operations-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    .compact-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
    .table-board{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
    .table-card{border:1px solid #dedbd5;border-radius:10px;background:#fff;padding:12px}
    .table-card strong{display:block;font-size:1.05rem}
    .table-card .status-pill{display:inline-flex;margin-top:7px}
    .kitchen-line{display:grid;grid-template-columns:minmax(0,1fr) 180px;gap:10px;align-items:center;border-bottom:1px solid #ebe8e2;padding:10px 0}
    .kitchen-line:last-child{border-bottom:0}
    .stock-low{color:#b42318;font-weight:800}
    .stock-ok{color:#00A651;font-weight:800}
    .report-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
    .report-card{background:#fffdfa;border:1px solid #dedbd5;border-radius:12px;padding:18px;min-height:170px}
    .report-row{display:flex;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid #ebe8e2}
    .report-row:last-child{border-bottom:0}
    .report-label{color:#8a8580;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
    .report-value{font-weight:900;color:#00A651;text-align:right}
    .report-empty{color:#8a8580;background:#f7f4ee;border-radius:9px;padding:12px}
    @media(max-width:1100px){.hospitality-form-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:1100px){.operations-grid{grid-template-columns:1fr}.compact-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:1100px){.report-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:900px){.restaurant-layout{grid-template-columns:1fr}}
    @media(max-width:640px){.hospitality-form-grid{grid-template-columns:1fr}.menu-row{grid-template-columns:1fr}.report-grid{grid-template-columns:1fr}.compact-grid{grid-template-columns:1fr}.kitchen-line{grid-template-columns:1fr}}
</style>

<div class="hospitality-panel p-3 mb-3">
    <h2 class="h5 mb-3">{{ $title }}</h2>

    @if(in_array($section, ['housekeeping', 'maintenance'], true) && isset($completionStats))
        <div class="row g-2 mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 bg-white">
                    <div class="text-muted small fw-bold text-uppercase">Completed</div>
                    <div class="h4 mb-0 text-success">{{ number_format($completionStats['completed'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 bg-white">
                    <div class="text-muted small fw-bold text-uppercase">{{ $section === 'maintenance' ? 'Open' : 'Pending' }}</div>
                    <div class="h4 mb-0">{{ number_format($completionStats['open'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    @endif

    @switch($section)
        @case('rooms')
            <form method="post" action="{{ route('hospitality.room-types.store') }}" class="hospitality-form-grid mb-3">
                @csrf
                <input class="form-control" name="name" placeholder="Room type: Standard, Deluxe, Executive, Suite" required>
                <input class="form-control" name="capacity" type="number" min="1" value="2" placeholder="Capacity" required>
                <input class="form-control" name="base_price" type="number" min="0" step="0.01" placeholder="Base price" required>
                <input class="form-control" name="amenities" placeholder="Amenities, comma separated">
                <button class="btn btn-success">Add Room Type</button>
            </form>
            <form method="post" action="{{ route('hospitality.rooms.store') }}" class="hospitality-form-grid">
                @csrf
                <select class="form-select" name="room_type_id"><option value="">Room type</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                <input class="form-control" name="room_number" placeholder="Room number" required>
                <select class="form-select" name="status">@foreach($statuses as $status)<option>{{ $status }}</option>@endforeach</select>
                <input class="form-control" name="capacity" type="number" min="1" value="2" placeholder="Capacity" required>
                <input class="form-control" name="floor" placeholder="Floor">
                <input class="form-control" name="view" placeholder="View">
                <input class="form-control" name="bed_type" placeholder="Bed type">
                <input class="form-control" name="amenities" placeholder="Amenities">
                <input class="form-control" name="price_per_night" type="number" min="0" step="0.01" placeholder="Price per night" required>
                <button class="btn btn-success">Add Room</button>
            </form>
            @break

        @case('reservations')
            <form method="post" action="{{ route('hospitality.reservations.store') }}" class="hospitality-form-grid">
                @csrf
                <select class="form-select" name="guest_profile_id" required><option value="">Guest</option>@foreach($guests as $guest)<option value="{{ $guest->id }}">{{ $guest->full_name }}</option>@endforeach</select>
                <select class="form-select" name="room_id"><option value="">Room</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_number }} - {{ $room->status }}</option>@endforeach</select>
                <select class="form-select" name="room_type_id"><option value="">Room type</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                <input class="form-control" name="arrival_date" type="date" required>
                <input class="form-control" name="departure_date" type="date" required>
                <input class="form-control" name="adults" type="number" min="1" value="1" placeholder="Adults" required>
                <input class="form-control" name="children" type="number" min="0" value="0" placeholder="Children">
                <select class="form-select" name="booking_source">@foreach($sources as $source)<option>{{ $source }}</option>@endforeach</select>
                <input class="form-control" name="deposit_amount" type="number" min="0" step="0.01" placeholder="Deposit">
                <input class="form-control" name="total_amount" type="number" min="0" step="0.01" placeholder="Total amount">
                <input class="form-control" name="special_requests" placeholder="Special requests">
                <button class="btn btn-success">Create Reservation</button>
            </form>
            @break

        @case('guests')
            <form method="post" action="{{ route('hospitality.guests.store') }}" class="hospitality-form-grid">
                @csrf
                <input class="form-control" name="full_name" placeholder="Full name" required>
                <input class="form-control" name="phone" placeholder="Phone">
                <input class="form-control" name="email" type="email" placeholder="Email">
                <input class="form-control" name="nationality" placeholder="Nationality">
                <input class="form-control" name="passport_number" placeholder="Passport number">
                <input class="form-control" name="id_number" placeholder="ID number">
                <input class="form-control" name="address" placeholder="Address">
                <select class="form-select" name="loyalty_level"><option value="">Loyalty level</option>@foreach($loyaltyLevels as $level)<option>{{ $level }}</option>@endforeach</select>
                <input class="form-control" name="preferences" placeholder="Preferences">
                <label class="form-check"><input class="form-check-input" type="checkbox" name="vip_status" value="1"> VIP status</label>
                <label class="form-check"><input class="form-check-input" type="checkbox" name="blacklist_flag" value="1"> Blacklist flag</label>
                <button class="btn btn-success">Create Guest</button>
            </form>
            @break

        @case('staff')
            <form method="post" action="{{ route('hospitality.staff.store') }}" class="hospitality-form-grid">
                @csrf
                <input class="form-control" name="name" placeholder="Staff name" required>
                <input class="form-control" name="email" type="email" placeholder="Email" required>
                <input class="form-control" name="username" placeholder="Username">
                <input class="form-control" name="employee_number" placeholder="Employee number">
                <select class="form-select" name="job_title" required>
                    <option value="">Staff title</option>
                    @foreach($titles as $titleOption)<option>{{ $titleOption }}</option>@endforeach
                </select>
                <select class="form-select" name="iam_role_id" required>
                    <option value="">Role</option>
                    @foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
                </select>
                <input class="form-control" name="phone" placeholder="Phone">
                <select class="form-select" name="status">@foreach($statuses as $status)<option>{{ $status }}</option>@endforeach</select>
                <input class="form-control" name="notes" placeholder="Notes">
                <button class="btn btn-success">Add Staff</button>
            </form>
            @break

        @case('suppliers')
            <form method="post" action="{{ route('hospitality.suppliers.store') }}" class="hospitality-form-grid">
                @csrf
                <input class="form-control" name="name" placeholder="Supplier name" required>
                <input class="form-control" name="email" type="email" placeholder="Email">
                <input class="form-control" name="phone" placeholder="Phone">
                <input class="form-control" name="kra_pin" placeholder="KRA PIN">
                <input class="form-control" name="address" placeholder="Address">
                <button class="btn btn-success">Activate Supplier</button>
            </form>
            @break

        @case('check-ins')
            <form method="post" action="{{ route('hospitality.check-ins.store') }}" class="hospitality-form-grid">
                @csrf
                <select class="form-select" name="reservation_id"><option value="">Reservation</option>@foreach($reservations as $reservation)<option value="{{ $reservation->id }}">{{ $reservation->reservation_number }} - {{ $reservation->guestProfile?->full_name ?? $reservation->client?->name }}</option>@endforeach</select>
                <select class="form-select" name="client_id"><option value="">Client / walk-in</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                <select class="form-select" name="room_id" required>
                    <option value="">Assign room</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}">
                            Room {{ $room->room_number }} - {{ $room->roomType?->name ?? 'Room' }} - Floor {{ $room->floor ?: 'n/a' }} - {{ $room->status }} - {{ number_format($room->price_per_night, 2) }}
                        </option>
                    @endforeach
                </select>
                <input class="form-control" name="arrival_date" type="date" value="{{ now()->toDateString() }}">
                <input class="form-control" name="departure_date" type="date" value="{{ now()->addDay()->toDateString() }}">
                <input class="form-control" name="access_code" placeholder="Access code">
                <input class="form-control" name="deposit_amount" type="number" min="0" step="0.01" placeholder="Deposit">
                <input class="form-control" name="notes" placeholder="Notes">
                <button class="btn btn-success">Check In</button>
            </form>
            @break

        @case('check-outs')
            <form method="post" action="{{ route('hospitality.check-outs.store') }}" class="hospitality-form-grid">
                @csrf
                <select class="form-select" name="reservation_id" required><option value="">Reservation</option>@foreach($reservations as $reservation)<option value="{{ $reservation->id }}">{{ $reservation->reservation_number }} - {{ $reservation->guestProfile?->full_name }}</option>@endforeach</select>
                <input class="form-control" name="restaurant_charges" type="number" min="0" step="0.01" placeholder="Restaurant charges">
                <input class="form-control" name="event_charges" type="number" min="0" step="0.01" placeholder="Event charges">
                <input class="form-control" name="other_charges" type="number" min="0" step="0.01" placeholder="Other services">
                <input class="form-control" name="payment_amount" type="number" min="0" step="0.01" placeholder="Payment collected">
                <input class="form-control" name="payment_method" placeholder="Payment method">
                <input class="form-control" name="payment_reference" placeholder="Reference">
                <button class="btn btn-success">Check Out</button>
            </form>
            @break

        @case('housekeeping')
            <form method="post" action="{{ route('hospitality.housekeeping.store') }}" class="hospitality-form-grid">
                @csrf
                <select class="form-select" name="room_id"><option value="">Room</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_number }}</option>@endforeach</select>
                <select class="form-select" name="task_type">@foreach($types as $type)<option>{{ $type }}</option>@endforeach</select>
                <select class="form-select" name="status">@foreach($statuses as $status)<option>{{ $status }}</option>@endforeach</select>
                <select class="form-select" name="assigned_to"><option value="">Assign staff</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
                <input class="form-control" name="notes" placeholder="Notes">
                <button class="btn btn-success">Create Task</button>
            </form>
            @break

        @case('maintenance')
            <form method="post" action="{{ route('hospitality.maintenance.store') }}" class="hospitality-form-grid">
                @csrf
                <select class="form-select" name="room_id"><option value="">Room</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_number }}</option>@endforeach</select>
                <select class="form-select" name="category">@foreach($categories as $category)<option>{{ $category }}</option>@endforeach</select>
                <select class="form-select" name="priority">@foreach($priorities as $priority)<option>{{ $priority }}</option>@endforeach</select>
                <select class="form-select" name="status">@foreach($statuses as $status)<option>{{ $status }}</option>@endforeach</select>
                <input class="form-control" name="title" placeholder="Title" required>
                <select class="form-select" name="assigned_to"><option value="">Assign officer</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
                <input class="form-control" name="description" placeholder="Description">
                <button class="btn btn-success">Create Request</button>
            </form>
            @break

        @case('restaurant')
            <div class="restaurant-layout mb-4">
                <div>
                    <form method="post" action="{{ route('hospitality.restaurant.menu-import') }}" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <label class="form-label">Upload menu CSV</label>
                        <input class="form-control mb-2" type="file" name="menu_file" accept=".csv,.txt" required>
                        <div class="small text-muted mb-2">Columns: category, name, description, price, sku, cost_price, stock.</div>
                        <button class="btn btn-outline-success w-100">Upload Menu</button>
                    </form>
                    <div class="border rounded p-3 mb-3">
                        <div class="text-muted small fw-bold text-uppercase">POS Menu</div>
                        <div class="h4 mb-1">{{ $menuItems->count() }}</div>
                        <div class="small text-muted">{{ $menuCategories->count() }} categories loaded from the existing POS catalog.</div>
                        <a class="btn btn-sm btn-outline-success mt-3" href="{{ route('public.hospitality.menu') }}" target="_blank">Open Guest Menu</a>
                    </div>
                    <div class="border rounded p-3">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Payment Methods</div>
                        @forelse($paymentMethods as $method)
                            <span class="badge text-bg-success me-1 mb-1">{{ $method->name }}</span>
                        @empty
                            <div class="small text-muted">Add payment methods in company settings.</div>
                        @endforelse
                    </div>
                </div>

                <form method="post" action="{{ route('hospitality.restaurant.store') }}" id="restaurant-order-form">
                    @csrf
                    <div class="hospitality-form-grid mb-3">
                        <select class="form-select" name="reservation_id"><option value="">Hotel reservation</option>@foreach($reservations as $reservation)<option value="{{ $reservation->id }}">{{ $reservation->reservation_number }} - {{ $reservation->guestProfile?->full_name }}</option>@endforeach</select>
                        <select class="form-select" name="guest_profile_id"><option value="">Walk-in guest</option>@foreach($guests as $guest)<option value="{{ $guest->id }}">{{ $guest->full_name }}</option>@endforeach</select>
                        <select class="form-select" name="order_type">@foreach($orderTypes as $type)<option>{{ $type }}</option>@endforeach</select>
                        <select class="form-select" name="restaurant_table_id"><option value="">Restaurant table</option>@foreach($restaurantTables as $table)<option value="{{ $table->id }}">{{ $table->table_number }} - {{ $table->status }}</option>@endforeach</select>
                        <input class="form-control" name="table_number" placeholder="Manual table">
                        <input class="form-control" name="reserved_for" type="datetime-local" placeholder="Reservation time">
                        <input class="form-control" name="party_size" type="number" min="1" value="1" placeholder="Guests">
                        <select class="form-select" name="waiter_id"><option value="">Waiter</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
                        <select class="form-select" name="payment_method_id"><option value="">Payment method</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select>
                        <select class="form-select" name="shipping_method"><option value="">Shipping method</option>@foreach($shippingMethods as $method)<option>{{ $method }}</option>@endforeach</select>
                        <select class="form-select" name="kitchen_status">@foreach($kitchenStatuses as $status)<option>{{ $status }}</option>@endforeach</select>
                        <select class="form-select" name="billing_status">@foreach($billingStatuses as $status)<option>{{ $status }}</option>@endforeach</select>
                        <input class="form-control" name="notes" placeholder="Notes">
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Food Menu</div>
                            <div class="fw-bold">Select uploaded menu items and quantities</div>
                        </div>
                        <div class="h5 mb-0">Total: <span id="restaurant-total">0.00</span></div>
                    </div>
                    <div class="menu-list">
                        @forelse($menuItems as $index => $item)
                            <div class="menu-row">
                                <div>
                                    <div class="fw-bold">{{ $item->name }}</div>
                                    <div class="small text-muted">{{ $item->category?->name ?? 'Restaurant Menu' }}</div>
                                    @if($item->description)<div class="small text-muted">{{ $item->description }}</div>@endif
                                    <div class="menu-price">{{ number_format($item->price, 2) }}</div>
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->id }}">
                                </div>
                                <input class="form-control qty-input restaurant-qty" name="items[{{ $index }}][quantity]" type="number" min="0" step="1" value="0" data-price="{{ $item->price }}">
                            </div>
                        @empty
                            <div class="alert alert-warning mb-0">Upload a menu CSV or add products in POS before creating restaurant reservations.</div>
                        @endforelse
                    </div>
                    <button class="btn btn-success mt-3" @disabled($menuItems->isEmpty())>Create Restaurant Order</button>
                </form>
            </div>

            <div class="operations-grid">
                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Restaurant Table Management</div>
                            <h3 class="h6 mb-0">Tables</h3>
                        </div>
                    </div>
                    <form method="post" action="{{ route('hospitality.restaurant.tables.store') }}" class="compact-grid mb-3">
                        @csrf
                        <input class="form-control" name="table_number" placeholder="Table number" required>
                        <input class="form-control" name="section" placeholder="Section">
                        <input class="form-control" name="capacity" type="number" min="1" value="2" placeholder="Capacity" required>
                        <select class="form-select" name="status">@foreach($tableStatuses as $status)<option>{{ $status }}</option>@endforeach</select>
                        <input class="form-control" name="notes" placeholder="Notes">
                        <button class="btn btn-success">Add Table</button>
                    </form>
                    <div class="table-board">
                        @forelse($restaurantTables as $table)
                            <div class="table-card">
                                <strong>{{ $table->table_number }}</strong>
                                <div class="small text-muted">{{ $table->section ?: 'Main floor' }} · {{ $table->capacity }} seats</div>
                                <span class="status-pill">{{ $table->status }}</span>
                                <form method="post" action="{{ route('hospitality.restaurant.tables.status', $table) }}" class="mt-2 d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-select form-select-sm" name="status">@foreach($tableStatuses as $status)<option @selected($table->status === $status)>{{ $status }}</option>@endforeach</select>
                                    <button class="btn btn-sm btn-outline-success">Save</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-muted small">No restaurant tables yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="border rounded p-3">
                    <div class="text-muted small fw-bold text-uppercase">Production Management</div>
                    <h3 class="h6 mb-3">Kitchen Queue</h3>
                    @forelse($records->take(8) as $order)
                        <div class="kitchen-line">
                            <div>
                                <div class="fw-bold">{{ $order->posOrder?->order_number ?? 'Restaurant order' }} · {{ $order->order_type }}</div>
                                <div class="small text-muted">{{ $order->restaurantTable?->table_number ?? $order->table_number ?? 'No table' }} · {{ number_format($order->total, 2) }}</div>
                            </div>
                            <form method="post" action="{{ route('hospitality.restaurant.production.update', $order) }}" class="d-flex gap-1">
                                @csrf
                                @method('PATCH')
                                <select class="form-select form-select-sm" name="kitchen_status">@foreach($kitchenStatuses as $status)<option @selected($order->kitchen_status === $status)>{{ $status }}</option>@endforeach</select>
                                <button class="btn btn-sm btn-outline-success">Save</button>
                            </form>
                        </div>
                    @empty
                        <div class="text-muted small">No kitchen orders yet.</div>
                    @endforelse
                </div>

                <div class="border rounded p-3">
                    <div class="text-muted small fw-bold text-uppercase">Unit & Ingredient</div>
                    <h3 class="h6 mb-3">Inventory Setup</h3>
                    <form method="post" action="{{ route('hospitality.restaurant.units.store') }}" class="compact-grid mb-3">
                        @csrf
                        <input class="form-control" name="name" placeholder="Unit name" required>
                        <input class="form-control" name="symbol" placeholder="Symbol" required>
                        <select class="form-select" name="type">@foreach($unitTypes as $type)<option>{{ $type }}</option>@endforeach</select>
                        <button class="btn btn-outline-success">Add Unit</button>
                    </form>
                    <form method="post" action="{{ route('hospitality.restaurant.ingredients.store') }}" class="compact-grid mb-3">
                        @csrf
                        <input class="form-control" name="name" placeholder="Ingredient" required>
                        <select class="form-select" name="unit_id"><option value="">Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>
                        <input class="form-control" name="sku" placeholder="SKU">
                        <input class="form-control" name="on_hand" type="number" min="0" step="0.001" placeholder="On hand">
                        <input class="form-control" name="reorder_level" type="number" min="0" step="0.001" placeholder="Reorder level">
                        <input class="form-control" name="cost_per_unit" type="number" min="0" step="0.01" placeholder="Cost">
                        <button class="btn btn-success">Add Ingredient</button>
                    </form>
                    <div class="row g-2">
                        @forelse($ingredients->take(8) as $ingredient)
                            <div class="col-sm-6">
                                <div class="border rounded p-2">
                                    <div class="fw-bold">{{ $ingredient->name }}</div>
                                    <div class="small {{ $ingredient->on_hand <= $ingredient->reorder_level ? 'stock-low' : 'stock-ok' }}">{{ number_format($ingredient->on_hand, 3) }} {{ $ingredient->unit?->symbol }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">No ingredients yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="border rounded p-3">
                    <div class="text-muted small fw-bold text-uppercase">Recipes</div>
                    <h3 class="h6 mb-3">Menu Ingredient Usage</h3>
                    <form method="post" action="{{ route('hospitality.restaurant.recipes.store') }}">
                        @csrf
                        <select class="form-select mb-2" name="product_id" required><option value="">Menu item</option>@foreach($menuItems as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
                        @for($i = 0; $i < 5; $i++)
                            <div class="d-flex gap-2 mb-2">
                                <select class="form-select" name="items[{{ $i }}][ingredient_id]"><option value="">Ingredient</option>@foreach($ingredients as $ingredient)<option value="{{ $ingredient->id }}">{{ $ingredient->name }}</option>@endforeach</select>
                                <input class="form-control" name="items[{{ $i }}][quantity]" type="number" min="0" step="0.001" placeholder="Qty">
                            </div>
                        @endfor
                        <button class="btn btn-success">Save Recipe</button>
                    </form>
                    <div class="mt-3">
                        @foreach($menuItems->take(5) as $item)
                            @php($recipeLines = $recipes->get($item->id, collect()))
                            @if($recipeLines->isNotEmpty())
                                <div class="small mb-2"><strong>{{ $item->name }}</strong>: {{ $recipeLines->map(fn($recipe) => $recipe->ingredient?->name.' '.$recipe->quantity.' '.$recipe->ingredient?->unit?->symbol)->join(', ') }}</div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="border rounded p-3">
                    <div class="text-muted small fw-bold text-uppercase">Purchase Management</div>
                    <h3 class="h6 mb-3">Ingredient Purchase</h3>
                    <form method="post" action="{{ route('hospitality.restaurant.purchases.store') }}">
                        @csrf
                        <div class="compact-grid mb-2">
                            <select class="form-select" name="supplier_id">
                                <option value="">Supplier</option>
                                @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                            </select>
                            <input class="form-control" name="supplier_name" placeholder="New supplier name">
                            <select class="form-select" name="status">@foreach($purchaseStatuses as $status)<option>{{ $status }}</option>@endforeach</select>
                            <select class="form-select" name="shipping_method"><option value="">Shipping method</option>@foreach($shippingMethods as $method)<option>{{ $method }}</option>@endforeach</select>
                            <input class="form-control" name="expected_at" type="date">
                            <input class="form-control" name="notes" placeholder="Notes">
                        </div>
                        @for($i = 0; $i < 5; $i++)
                            <div class="compact-grid mb-2">
                                <select class="form-select" name="items[{{ $i }}][ingredient_id]"><option value="">Ingredient</option>@foreach($ingredients as $ingredient)<option value="{{ $ingredient->id }}">{{ $ingredient->name }}</option>@endforeach</select>
                                <input class="form-control" name="items[{{ $i }}][description]" placeholder="Description">
                                <input class="form-control" name="items[{{ $i }}][quantity]" type="number" min="0" step="0.001" placeholder="Qty">
                                <input class="form-control" name="items[{{ $i }}][unit_cost]" type="number" min="0" step="0.01" placeholder="Unit cost">
                            </div>
                        @endfor
                        <button class="btn btn-success">Save Purchase</button>
                    </form>
                </div>

                <div class="border rounded p-3">
                    <div class="text-muted small fw-bold text-uppercase">Recent Purchases</div>
                    <h3 class="h6 mb-3">Receiving</h3>
                    @forelse($purchases as $purchase)
                        <div class="d-flex justify-content-between gap-2 border-bottom py-2">
                            <div>
                                <div class="fw-bold">{{ $purchase->purchase_number }}</div>
                                <div class="small text-muted">{{ $purchase->supplier?->name ?? $purchase->supplier_name }} · {{ $purchase->shipping_method ?: 'No shipping' }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">{{ number_format($purchase->total, 2) }}</div>
                                <span class="status-pill">{{ $purchase->status }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">No purchases yet.</div>
                    @endforelse
                </div>

                <div class="border rounded p-3">
                    <div class="text-muted small fw-bold text-uppercase">Human Resources & Accounts</div>
                    <h3 class="h6 mb-3">Platform Integration</h3>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-4"><div class="border rounded p-2"><div class="small text-muted">Staff</div><div class="fw-bold">{{ $users->count() }}</div></div></div>
                        <div class="col-sm-4"><div class="border rounded p-2"><div class="small text-muted">Payment Methods</div><div class="fw-bold">{{ $paymentMethods->count() }}</div></div></div>
                        <div class="col-sm-4"><div class="border rounded p-2"><div class="small text-muted">POS Orders</div><div class="fw-bold">{{ $records->total() }}</div></div></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-outline-success" href="{{ route('administration.index') }}">Manage Staff</a>
                        <a class="btn btn-sm btn-outline-success" href="{{ route('settings.edit') }}">Payment Methods</a>
                        <a class="btn btn-sm btn-outline-success" href="{{ route('finance.index') }}">Finance Accounts</a>
                    </div>
                </div>
            </div>
            @break

        @case('events')
            <form method="post" action="{{ route('hospitality.events.store') }}" class="hospitality-form-grid">
                @csrf
                <select class="form-select" name="client_id"><option value="">Corporate client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                <select class="form-select" name="guest_profile_id"><option value="">Guest</option>@foreach($guests as $guest)<option value="{{ $guest->id }}">{{ $guest->full_name }}</option>@endforeach</select>
                <input class="form-control" name="venue_name" placeholder="Venue or conference room" required>
                <input class="form-control" name="event_type" placeholder="Event type">
                <input class="form-control" name="starts_at" type="datetime-local" required>
                <input class="form-control" name="ends_at" type="datetime-local" required>
                <input class="form-control" name="attendees" type="number" min="0" placeholder="Attendees">
                <input class="form-control" name="package_name" placeholder="Package">
                <select class="form-select" name="status">@foreach($statuses as $status)<option>{{ $status }}</option>@endforeach</select>
                <input class="form-control" name="total_amount" type="number" min="0" step="0.01" placeholder="Total amount">
                <button class="btn btn-success">Create Event</button>
            </form>
            @break
    @endswitch
</div>

@if($section === 'reports')
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div class="text-muted small fw-bold text-uppercase">Real-time Reports</div>
        <div class="small text-muted" id="reports-refreshed-at">Auto refreshing every 30 seconds</div>
    </div>
    <div class="report-grid">
        @foreach($reports as $name => $report)
            <div class="report-card" data-report-card="{{ $name }}">
                <h3 class="h5 mb-3">{{ $name }}</h3>
                @if(empty($report))
                    <div class="report-empty">No activity recorded yet.</div>
                @else
                    @foreach($report as $label => $value)
                        <div class="report-row" data-report-row="{{ $label }}">
                            <span class="report-label">{{ str($label)->replace('_', ' ')->headline() }}</span>
                            <span class="report-value" data-report-value>
                                @if(is_numeric($value))
                                    {{ str_contains(strtolower((string) $label), 'rate') ? number_format($value, 1).'%' : number_format($value, 2) }}
                                @else
                                    {{ $value }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const formatReportValue = (label, value) => {
            if (typeof value === 'number') {
                return String(label).toLowerCase().includes('rate')
                    ? `${value.toFixed(1)}%`
                    : value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            return value ?? '';
        };

        const renderReports = (reports) => {
            Object.entries(reports || {}).forEach(([name, report]) => {
                const card = document.querySelector(`[data-report-card="${CSS.escape(name)}"]`);
                if (!card) return;

                Object.entries(report || {}).forEach(([label, value]) => {
                    const row = card.querySelector(`[data-report-row="${CSS.escape(label)}"] [data-report-value]`);
                    if (row) row.textContent = formatReportValue(label, value);
                });
            });
        };

        const refresh = async () => {
            try {
                const response = await fetch('{{ route('hospitality.reports.live') }}', { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const payload = await response.json();
                renderReports(payload.reports);
                const marker = document.getElementById('reports-refreshed-at');
                if (marker) marker.textContent = `Updated ${new Date(payload.generated_at).toLocaleTimeString()}`;
            } catch (error) {
            }
        };

        window.setInterval(refresh, 30000);
    });
    </script>
    @endpush
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Record</th><th>Details</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>
                            @if($section === 'rooms')
                                Room {{ $record->room_number }}<div class="small text-muted">{{ $record->roomType?->name }}</div>
                            @elseif($section === 'reservations')
                                {{ $record->reservation_number }}<div class="small text-muted">{{ $record->guestProfile?->full_name ?? 'Guest' }}</div>
                            @elseif($section === 'guests')
                                {{ $record->full_name }}<div class="small text-muted">{{ $record->email ?: $record->phone }}</div>
                            @elseif($section === 'staff')
                                {{ $record->name }}<div class="small text-muted">{{ $record->email ?: $record->phone }}</div>
                            @elseif($section === 'suppliers')
                                {{ $record->name }}<div class="small text-muted">{{ $record->email ?: $record->phone }}</div>
                            @elseif($section === 'check-ins')
                                {{ $record->reservation?->reservation_number }}<div class="small text-muted">Room {{ $record->room?->room_number }}</div>
                            @elseif($section === 'check-outs')
                                {{ $record->reservation?->reservation_number }}<div class="small text-muted">{{ $record->invoice?->invoice_number }}</div>
                            @elseif($section === 'housekeeping')
                                {{ $record->task_type }}<div class="small text-muted">Room {{ $record->room?->room_number ?? 'n/a' }}</div>
                            @elseif($section === 'maintenance')
                                {{ $record->title }}<div class="small text-muted">{{ $record->category }}</div>
                            @elseif($section === 'restaurant')
                                {{ $record->order_type ?? 'Restaurant' }}<div class="small text-muted">Table {{ ($record->restaurantTable?->table_number ?? $record->table_number) ?: 'n/a' }} · {{ $record->posOrder?->order_number ?: 'Hospitality order' }}</div>
                            @elseif($section === 'events')
                                {{ $record->booking_number }}<div class="small text-muted">{{ $record->venue_name }}</div>
                            @endif
                        </td>
                        <td>
                            @if($section === 'reservations')
                                {{ $record->arrival_date?->format('d M Y') }} - {{ $record->departure_date?->format('d M Y') }}
                            @elseif($section === 'guests')
                                {{ $record->nationality ?: 'Nationality n/a' }} · {{ $record->loyalty_level ?: 'No loyalty' }}
                            @elseif($section === 'staff')
                                @php($membership = $memberships->get($record->id))
                                {{ $record->job_title ?: 'No title' }} · {{ $roles->firstWhere('id', $membership?->iam_role_id)?->name ?? 'No role' }}
                            @elseif($section === 'suppliers')
                                {{ $record->kra_pin ?: 'No KRA PIN' }} · {{ $record->address ?: 'No address' }}
                            @elseif($section === 'events')
                                {{ $record->starts_at?->format('d M Y H:i') }} · {{ number_format($record->total_amount, 2) }}
                            @elseif($section === 'restaurant')
                                {{ $record->reserved_for?->format('d M Y H:i') ?? 'Immediate' }} · {{ $record->party_size ?? 1 }} guests · {{ number_format($record->total, 2) }} · {{ $record->paymentMethod?->name ?? 'Payment open' }}
                            @elseif($section === 'housekeeping' && $record->status === 'Completed')
                                Completed {{ $record->completed_at?->format('d M Y H:i') ?? 'recently' }}
                            @elseif($section === 'maintenance' && in_array($record->status, ['Resolved', 'Closed'], true))
                                Completed {{ ($record->closed_at ?? $record->resolved_at)?->format('d M Y H:i') ?? 'recently' }}
                            @else
                                {{ $record->created_at?->format('d M Y H:i') }}
                            @endif
                        </td>
                        <td><span class="status-pill">{{ $record->status ?? $record->kitchen_status ?? $record->loyalty_level ?? 'Active' }}</span></td>
                        <td>
                            @if($section === 'rooms')
                                <form method="post" action="{{ route('hospitality.rooms.status', $record) }}" class="d-flex gap-1">@csrf @method('PATCH')<select class="form-select form-select-sm" name="status">@foreach($statuses as $status)<option @selected($record->status === $status)>{{ $status }}</option>@endforeach</select><button class="btn btn-sm btn-outline-success">Save</button></form>
                            @elseif($section === 'guests' && ! $record->loyaltyMember)
                                <form method="post" action="{{ route('hospitality.guests.loyalty', $record) }}">@csrf<button class="btn btn-sm btn-outline-success">Enroll Loyalty</button></form>
                            @elseif($section === 'staff')
                                @php($membership = $memberships->get($record->id))
                                <form method="post" action="{{ route('hospitality.staff.update', $record) }}" class="d-flex flex-wrap gap-1">@csrf @method('PATCH')<select class="form-select form-select-sm" name="job_title">@foreach($titles as $titleOption)<option @selected($record->job_title === $titleOption)>{{ $titleOption }}</option>@endforeach</select><select class="form-select form-select-sm" name="iam_role_id">@foreach($roles as $role)<option value="{{ $role->id }}" @selected((int) $membership?->iam_role_id === $role->id)>{{ $role->name }}</option>@endforeach</select><select class="form-select form-select-sm" name="status">@foreach($statuses as $status)<option @selected(($record->status ?? 'Active') === $status)>{{ $status }}</option>@endforeach</select><button class="btn btn-sm btn-outline-success">Save</button></form>
                            @elseif($section === 'suppliers')
                                <a class="btn btn-sm btn-outline-success" href="{{ route('erp.procurement') }}">Procurement</a>
                            @elseif($section === 'housekeeping')
                                <form method="post" action="{{ route('hospitality.housekeeping.update', $record) }}" class="d-flex gap-1">@csrf @method('PATCH')<select class="form-select form-select-sm" name="status">@foreach($statuses as $status)<option @selected($record->status === $status)>{{ $status }}</option>@endforeach</select><button class="btn btn-sm btn-outline-success">Save</button></form>
                            @elseif($section === 'maintenance')
                                <form method="post" action="{{ route('hospitality.maintenance.update', $record) }}" class="d-flex gap-1">@csrf @method('PATCH')<select class="form-select form-select-sm" name="status">@foreach($statuses as $status)<option @selected($record->status === $status)>{{ $status }}</option>@endforeach</select><button class="btn btn-sm btn-outline-success">Save</button></form>
                            @elseif($section === 'restaurant')
                                <form method="post" action="{{ route('hospitality.restaurant.production.update', $record) }}" class="d-flex gap-1">@csrf @method('PATCH')<select class="form-select form-select-sm" name="kitchen_status">@foreach($kitchenStatuses as $status)<option @selected($record->kitchen_status === $status)>{{ $status }}</option>@endforeach</select><button class="btn btn-sm btn-outline-success">Save</button></form>
                            @else
                                <span class="text-muted small">Managed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No {{ strtolower($title) }} records yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($records, 'links'))
            <div class="card-body">{{ $records->links() }}</div>
        @endif
    </div>
@endif
@if($section === 'restaurant')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const total = document.getElementById('restaurant-total');
    const inputs = document.querySelectorAll('.restaurant-qty');
    const render = () => {
        const amount = Array.from(inputs).reduce((sum, input) => {
            return sum + (Number(input.value || 0) * Number(input.dataset.price || 0));
        }, 0);
        total.textContent = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    inputs.forEach((input) => input.addEventListener('input', render));
    render();
});
</script>
@endpush
@endif
@endsection
