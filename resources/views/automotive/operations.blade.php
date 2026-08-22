@extends('layouts.app')
@section('title', 'Automotive - '.$title)

@section('content')
@php
    $jobStatuses = ['Draft','Bookings','Checked In','Inspection','Diagnosis','Awaiting Customer Approval','Awaiting Parts','Ready','In Progress','Quality Check','Ready for Collection','Completed','On Hold','Cancelled'];
    $vehicleOptions = $vehiclesList ?? collect();
    $jobOptions = $jobsList ?? collect();
@endphp
<style>
    .auto-shell{display:grid;gap:16px}
    .auto-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
    .auto-kicker{color:#007a3b;font-size:.74rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}
    .auto-title{font-size:clamp(1.8rem,3vw,2.7rem);font-weight:900;margin:0;color:#050806}
    .auto-card{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:16px;box-shadow:0 12px 28px rgba(15,23,42,.05)}
    .auto-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    .auto-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .auto-list{display:grid;gap:10px}
    .auto-row{display:flex;justify-content:space-between;gap:12px;border:1px solid #edf0f4;border-radius:10px;padding:12px;background:#fff}
    .auto-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .62rem;background:#e9fff2;color:#007a3b;font-size:.75rem;font-weight:900}
    .auto-actions{display:flex;gap:8px;flex-wrap:wrap}
    .auto-actions a{border-radius:999px}
    .auto-board{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px}
    .auto-column{border:1px solid #e7e9ee;border-radius:12px;padding:12px;background:#fbfffc;min-height:160px}
    @media(max-width:980px){.auto-grid,.auto-grid-2{grid-template-columns:1fr}.auto-row{display:grid}}
</style>

<div class="auto-shell">
    <div class="auto-head">
        <div>
            <div class="auto-kicker">Automotive</div>
            <h1 class="auto-title">{{ $title }}</h1>
            <p class="text-muted mb-0" style="max-width:840px">{{ $description }}</p>
        </div>
        <div class="auto-actions">
            <a class="btn btn-dark btn-sm" href="{{ route('automotive.dashboard') }}">Dashboard</a>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('automotive.vehicles') }}">Vehicles</a>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('automotive.job-cards') }}">Jobs</a>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('automotive.reports') }}">Reports</a>
        </div>
    </div>

    @if(session('success') || session('status'))
        <div class="alert alert-success">{{ session('success') ?: session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if(($section ?? '') === 'vehicles')
        <div class="auto-card">
            <h2 class="h5">Register vehicle</h2>
            <form method="post" action="{{ route('automotive.vehicles.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="client_id" class="form-select"><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="registration_number" class="form-control" placeholder="Registration" required></div>
                <div class="col-md-2"><input name="vin" class="form-control" placeholder="VIN"></div>
                <div class="col-md-2"><input name="make" class="form-control" placeholder="Make"></div>
                <div class="col-md-2"><input name="model" class="form-control" placeholder="Model"></div>
                <div class="col-md-1"><input name="year" type="number" class="form-control" placeholder="Year"></div>
                <div class="col-md-2"><input name="mileage" type="number" min="0" class="form-control" placeholder="Mileage"></div>
                <div class="col-md-2"><select name="fuel_type" class="form-select"><option value="">Fuel</option><option>Petrol</option><option>Diesel</option><option>Hybrid</option><option>Electric</option><option>Other</option></select></div>
                <div class="col-md-2"><input name="next_service_date" type="date" class="form-control"></div>
                <div class="col-md-2"><input name="next_service_mileage" type="number" min="0" class="form-control" placeholder="Next mileage"></div>
                <div class="col-md-2"><button class="btn btn-success w-100">Save Vehicle</button></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @forelse($vehicles as $vehicle)
                <div class="auto-row">
                    <div><strong>{{ $vehicle->registration_number }}</strong><div class="text-muted small">{{ trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: 'Vehicle' }} · {{ $vehicle->client?->name ?? 'No customer' }}</div></div>
                    <span class="auto-pill">{{ $vehicle->status }}</span>
                </div>
            @empty
                <div class="text-muted">No vehicles registered.</div>
            @endforelse
            {{ $vehicles->links() }}
        </div>

    @elseif(($section ?? '') === 'bookings')
        <div class="auto-grid-2">
            <div class="auto-card">
                <h2 class="h5">Create booking</h2>
                <form method="post" action="{{ route('automotive.bookings.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-6"><select name="client_id" class="form-select"><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><select name="vehicle_id" class="form-select"><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                    <div class="col-md-6"><input name="requested_service" class="form-control" placeholder="Requested service"></div>
                    <div class="col-md-3"><input name="preferred_date" type="date" class="form-control"></div>
                    <div class="col-md-3"><input name="preferred_time" type="time" class="form-control"></div>
                    <div class="col-12"><textarea name="customer_complaint" class="form-control" placeholder="Customer complaint"></textarea></div>
                    <div class="col-md-4"><select name="status" class="form-select"><option>Pending</option><option>Confirmed</option><option>Checked In</option></select></div>
                    <div class="col-md-4"><button class="btn btn-success w-100">Create Booking</button></div>
                </form>
            </div>
            <div class="auto-card">
                <h2 class="h5">Check in vehicle</h2>
                <form method="post" action="{{ route('automotive.check-ins.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-6"><select name="vehicle_id" class="form-select" required><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                    <div class="col-md-6"><select name="booking_id" class="form-select"><option value="">Booking</option>@foreach($bookingsList as $booking)<option value="{{ $booking->id }}">{{ $booking->booking_number }}</option>@endforeach</select></div>
                    <div class="col-md-4"><input name="mileage" type="number" min="0" class="form-control" placeholder="Mileage"></div>
                    <div class="col-md-4"><input name="fuel_level" class="form-control" placeholder="Fuel level"></div>
                    <div class="col-md-4"><input name="expected_completion" type="datetime-local" class="form-control"></div>
                    <div class="col-12"><textarea name="customer_complaint" class="form-control" placeholder="Complaint / authorization notes"></textarea></div>
                    <div class="col-md-4 form-check ms-2"><input name="keys_received" value="1" type="checkbox" class="form-check-input" id="keys"><label class="form-check-label" for="keys">Keys received</label></div>
                    <div class="col-md-4"><button class="btn btn-dark w-100">Check In</button></div>
                </form>
            </div>
        </div>
        <div class="auto-card auto-list">
            @foreach($bookings as $booking)
                <div class="auto-row"><div><strong>{{ $booking->booking_number }}</strong><div class="text-muted small">{{ $booking->vehicle?->registration_number }} · {{ $booking->requested_service }}</div></div><span class="auto-pill">{{ $booking->status }}</span></div>
            @endforeach
            {{ $bookings->links() }}
        </div>

    @elseif(($section ?? '') === 'job-cards')
        <div class="auto-card">
            <h2 class="h5">Open job card</h2>
            <form method="post" action="{{ route('automotive.job-cards.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="vehicle_id" class="form-select" required><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="client_id" class="form-select"><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="technician_id" class="form-select"><option value="">Technician</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="estimated_completion" type="datetime-local" class="form-control"></div>
                <div class="col-md-2"><select name="priority" class="form-select"><option>Normal</option><option>Urgent</option><option>High</option></select></div>
                <div class="col-md-6"><textarea name="customer_complaint" class="form-control" placeholder="Customer complaint"></textarea></div>
                <div class="col-md-6"><textarea name="work_requested" class="form-control" placeholder="Work requested"></textarea></div>
                <div class="col-md-2"><button class="btn btn-success w-100">Open Job</button></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($jobs as $job)
                <div class="auto-row">
                    <div><strong>{{ $job->job_number }}</strong><div class="text-muted small">{{ $job->vehicle?->registration_number }} · {{ $job->client?->name ?? 'No customer' }}</div></div>
                    <form method="post" action="{{ route('automotive.job-cards.status', $job) }}" class="d-flex gap-2">
                        @csrf
                        <select name="status" class="form-select form-select-sm">@foreach($jobStatuses as $status)<option @selected($job->status === $status)>{{ $status }}</option>@endforeach</select>
                        <button class="btn btn-sm btn-outline-dark">Update</button>
                    </form>
                    <form method="post" action="{{ route('automotive.job-cards.invoice', $job) }}">@csrf<button class="btn btn-sm btn-success">Invoice</button></form>
                </div>
                <form method="post" action="{{ route('automotive.job-cards.labour-tasks.store', $job) }}" class="row g-2 border rounded p-2">
                    @csrf
                    <div class="col-md-4"><input name="description" class="form-control form-control-sm" placeholder="Labour task" required></div>
                    <div class="col-md-2"><input name="billable_hours" type="number" min="0" step="0.01" class="form-control form-control-sm" placeholder="Hours"></div>
                    <div class="col-md-2"><input name="hourly_rate" type="number" min="0" step="0.01" class="form-control form-control-sm" placeholder="Rate"></div>
                    <div class="col-md-2"><select name="technician_id" class="form-select form-select-sm"><option value="">Tech</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Add Labour</button></div>
                </form>
            @endforeach
            {{ $jobs->links() }}
        </div>

    @elseif(($section ?? '') === 'estimates')
        <div class="auto-card">
            <h2 class="h5">Create estimate</h2>
            <form method="post" action="{{ route('automotive.estimates.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="job_card_id" class="form-select"><option value="">Job card</option>@foreach($jobOptions as $job)<option value="{{ $job->id }}">{{ $job->job_number }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="vehicle_id" class="form-select"><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="client_id" class="form-select"><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="discount_total" type="number" min="0" step="0.01" class="form-control" placeholder="Discount"></div>
                <div class="col-md-1"><button class="btn btn-success w-100">Save</button></div>
                @foreach([0 => 'Labour', 1 => 'Part', 2 => 'Consumable'] as $i => $type)
                    <div class="col-md-2"><select name="items[{{ $i }}][type]" class="form-select"><option>{{ $type }}</option><option>External Service</option></select></div>
                    <div class="col-md-4"><input name="items[{{ $i }}][description]" class="form-control" placeholder="{{ $type }} description"></div>
                    <div class="col-md-2"><input name="items[{{ $i }}][quantity]" type="number" min="0" step="0.01" class="form-control" placeholder="Qty" value="{{ $i === 0 ? 1 : '' }}"></div>
                    <div class="col-md-2"><input name="items[{{ $i }}][unit_price]" type="number" min="0" step="0.01" class="form-control" placeholder="Unit price"></div>
                    <div class="col-md-2"><input name="items[{{ $i }}][tax_rate]" type="number" min="0" step="0.01" class="form-control" placeholder="Tax %"></div>
                @endforeach
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($estimates as $estimate)
                <div class="auto-row">
                    <div><strong>{{ $estimate->estimate_number }}</strong><div class="small text-muted">{{ $estimate->vehicle?->registration_number }} · {{ $estimate->client?->name ?? 'No customer' }}</div></div>
                    <span class="auto-pill">{{ $estimate->status }}</span>
                    <strong>{{ number_format((float) $estimate->total, 2) }}</strong>
                    <form method="post" action="{{ route('automotive.estimates.approve', $estimate) }}">@csrf<button class="btn btn-sm btn-outline-dark">Approve</button></form>
                    <form method="post" action="{{ route('automotive.estimates.quotation', $estimate) }}">@csrf<button class="btn btn-sm btn-success">Quotation</button></form>
                </div>
            @endforeach
            {{ $estimates->links() }}
        </div>

    @elseif(($section ?? '') === 'labour-operations')
        <div class="auto-card">
            <h2 class="h5">Standard labour operation</h2>
            <form method="post" action="{{ route('automotive.labour-operations.store') }}" class="row g-2">
                @csrf
                <div class="col-md-2"><input name="labour_code" class="form-control" placeholder="Code" required></div>
                <div class="col-md-3"><input name="name" class="form-control" placeholder="Operation name" required></div>
                <div class="col-md-2"><input name="standard_hours" type="number" min="0" step="0.01" class="form-control" placeholder="Hours"></div>
                <div class="col-md-2"><input name="hourly_rate" type="number" min="0" step="0.01" class="form-control" placeholder="Rate"></div>
                <div class="col-md-2"><input name="skill_required" class="form-control" placeholder="Skill"></div>
                <div class="col-md-1"><button class="btn btn-success w-100">Save</button></div>
                <div class="col-12"><textarea name="description" class="form-control" placeholder="Description"></textarea></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($operations as $operation)
                <div class="auto-row"><div><strong>{{ $operation->labour_code }}</strong><div class="small text-muted">{{ $operation->name }} · {{ $operation->skill_required }}</div></div><span class="auto-pill">{{ number_format((float) $operation->standard_hours, 2) }} hrs</span><strong>{{ number_format((float) $operation->hourly_rate, 2) }}</strong></div>
            @endforeach
            {{ $operations->links() }}
        </div>

    @elseif(($section ?? '') === 'technicians')
        <div class="auto-card auto-list">
            @foreach($technicians as $technician)
                @php
                    $openJobs = $jobOptions->where('technician_id', $technician->id)->whereNotIn('status', ['Completed', 'Cancelled'])->count();
                    $tasks = ($labourTasks ?? collect())->where('technician_id', $technician->id)->count();
                @endphp
                <div class="auto-row">
                    <div><strong>{{ $technician->name }}</strong><div class="small text-muted">{{ $technician->email ?? $technician->phone ?? 'Technician' }}</div></div>
                    <span class="auto-pill">{{ $openJobs }} open jobs</span>
                    <span class="auto-pill">{{ $tasks }} tasks</span>
                </div>
            @endforeach
            {{ $technicians->links() }}
        </div>

    @elseif(($section ?? '') === 'workshop')
        <div class="auto-grid-2">
            <div class="auto-card">
                <h2 class="h5">Workshop bay</h2>
                <form method="post" action="{{ route('automotive.bays.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><input name="name" class="form-control" placeholder="Bay name" required></div>
                    <div class="col-md-4"><input name="type" class="form-control" placeholder="Type"></div>
                    <div class="col-md-4"><select name="status" class="form-select"><option>Available</option><option>In Use</option><option>Offline</option></select></div>
                    <div class="col-md-4"><button class="btn btn-success w-100">Save Bay</button></div>
                </form>
            </div>
            <div class="auto-card">
                <h2 class="h5">Record diagnostic</h2>
                <form method="post" action="{{ $jobOptions->first() ? route('automotive.diagnostics.store', $jobOptions->first()) : '#' }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><select class="form-select" onchange="this.form.action=this.value"><option value="#">Job</option>@foreach($jobOptions as $job)<option value="{{ route('automotive.diagnostics.store', $job) }}">{{ $job->job_number }}</option>@endforeach</select></div>
                    <div class="col-md-4"><input name="diagnostic_type" class="form-control" placeholder="Diagnostic type"></div>
                    <div class="col-md-4"><input name="diagnosis" class="form-control" placeholder="Diagnosis"></div>
                    <div class="col-12"><textarea name="recommended_repair" class="form-control" placeholder="Recommended repair"></textarea></div>
                    <div class="col-md-4"><button class="btn btn-dark w-100">Save Diagnostic</button></div>
                </form>
            </div>
        </div>
        <div class="auto-board">
            @foreach($board as $status => $items)
                <div class="auto-column"><div class="auto-kicker mb-2">{{ $status }}</div>@forelse($items as $job)<div class="auto-row mb-2"><div><strong>{{ $job->job_number }}</strong><div class="small text-muted">{{ $job->vehicle?->registration_number }}</div></div></div>@empty<div class="text-muted small">No jobs.</div>@endforelse</div>
            @endforeach
        </div>

    @elseif(($section ?? '') === 'inspections')
        <div class="auto-card">
            <h2 class="h5">Create inspection</h2>
            <form method="post" action="{{ route('automotive.inspections.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="vehicle_id" class="form-select" required><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="job_card_id" class="form-select"><option value="">Job card</option>@foreach($jobOptions as $job)<option value="{{ $job->id }}">{{ $job->job_number }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="inspection_date" type="date" class="form-control"></div>
                <div class="col-md-2"><input name="estimated_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Estimate"></div>
                <div class="col-md-2"><button class="btn btn-success w-100">Create</button></div>
                <div class="col-12"><textarea name="recommendations" class="form-control" placeholder="Recommendations"></textarea></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($inspections as $inspection)
                <div class="auto-row"><div><strong>{{ $inspection->inspection_number }}</strong><div class="small text-muted">{{ $inspection->vehicle?->registration_number }} · {{ $inspection->status }}</div></div><form method="post" action="{{ route('automotive.inspections.estimate', $inspection) }}">@csrf<button class="btn btn-sm btn-outline-dark">Create Estimate</button></form></div>
            @endforeach
            {{ $inspections->links() }}
        </div>

    @elseif(($section ?? '') === 'parts')
        <div class="auto-grid-2">
            <div class="auto-card">
                <h2 class="h5">Add part</h2>
                <form method="post" action="{{ route('automotive.parts.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><input name="part_number" class="form-control" placeholder="Part number" required></div>
                    <div class="col-md-8"><input name="name" class="form-control" placeholder="Part name" required></div>
                    <div class="col-md-4"><input name="category" class="form-control" placeholder="Category"></div>
                    <div class="col-md-4"><input name="cost_price" type="number" min="0" step="0.01" class="form-control" placeholder="Cost"></div>
                    <div class="col-md-4"><input name="selling_price" type="number" min="0" step="0.01" class="form-control" placeholder="Selling"></div>
                    <div class="col-md-4"><input name="stock_quantity" type="number" min="0" step="0.01" class="form-control" placeholder="Stock"></div>
                    <div class="col-md-4"><input name="reorder_level" type="number" min="0" step="0.01" class="form-control" placeholder="Reorder"></div>
                    <div class="col-md-4"><button class="btn btn-success w-100">Save Part</button></div>
                </form>
            </div>
            <div class="auto-card">
                <h2 class="h5">Request part for job</h2>
                <form method="post" action="{{ $jobOptions->first() ? route('automotive.part-requests.store', $jobOptions->first()) : '#' }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><select class="form-select" onchange="this.form.action=this.value"><option value="#">Job</option>@foreach($jobOptions as $job)<option value="{{ route('automotive.part-requests.store', $job) }}">{{ $job->job_number }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="part_id" class="form-select"><option value="">Part</option>@foreach($partsList as $part)<option value="{{ $part->id }}">{{ $part->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><input name="requested_qty" type="number" min="0.001" step="0.001" class="form-control" placeholder="Qty" required></div>
                    <div class="col-md-2"><button class="btn btn-dark w-100">Request</button></div>
                </form>
            </div>
        </div>
        <div class="auto-card auto-list">
            @foreach($parts as $part)
                <div class="auto-row"><div><strong>{{ $part->part_number }}</strong><div class="small text-muted">{{ $part->name }} · {{ $part->category }}</div></div><span class="auto-pill">{{ number_format((float) $part->stock_quantity, 2) }} in stock</span></div>
            @endforeach
            {{ $parts->links() }}
        </div>

    @elseif(in_array(($section ?? ''), ['quality','warranty']))
        <div class="auto-grid-2">
            <div class="auto-card">
                <h2 class="h5">Quality check</h2>
                <form method="post" action="{{ $jobOptions->first() ? route('automotive.quality.store', $jobOptions->first()) : '#' }}" class="row g-2">
                    @csrf
                    <div class="col-md-5"><select class="form-select" onchange="this.form.action=this.value"><option value="#">Job</option>@foreach($jobOptions as $job)<option value="{{ route('automotive.quality.store', $job) }}">{{ $job->job_number }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="result" class="form-select"><option>Pass</option><option>Conditional Pass</option><option>Fail</option></select></div>
                    <div class="col-md-3"><button class="btn btn-success w-100">Record QC</button></div>
                    <div class="col-12"><textarea name="failure_reason" class="form-control" placeholder="Failure reason / corrective action"></textarea></div>
                </form>
            </div>
            <div class="auto-card">
                <h2 class="h5">Warranty / comeback</h2>
                <form method="post" action="{{ route('automotive.warranties.store') }}" class="row g-2 mb-2">
                    @csrf
                    <div class="col-md-4"><select name="vehicle_id" class="form-select"><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                    <div class="col-md-3"><input name="type" class="form-control" placeholder="Type"></div>
                    <div class="col-md-3"><input name="warranty_end" type="date" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-dark w-100">Save</button></div>
                </form>
                <form method="post" action="{{ $jobOptions->first() ? route('automotive.comebacks.store', $jobOptions->first()) : '#' }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><select class="form-select" onchange="this.form.action=this.value"><option value="#">Job</option>@foreach($jobOptions as $job)<option value="{{ route('automotive.comebacks.store', $job) }}">{{ $job->job_number }}</option>@endforeach</select></div>
                    <div class="col-md-6"><input name="complaint" class="form-control" placeholder="Comeback complaint" required></div>
                    <div class="col-md-2"><button class="btn btn-outline-dark w-100">Open</button></div>
                </form>
            </div>
        </div>
        <div class="auto-card auto-list">
            @foreach(($qualityChecks ?? $warranties ?? collect()) as $item)
                <div class="auto-row"><strong>{{ $item->qc_number ?? $item->warranty_number }}</strong><span class="auto-pill">{{ $item->result ?? $item->status }}</span></div>
            @endforeach
        </div>

    @elseif(($section ?? '') === 'road-tests')
        <div class="auto-card">
            <h2 class="h5">Record road test</h2>
            <form method="post" action="{{ $jobOptions->first() ? route('automotive.road-tests.store', $jobOptions->first()) : '#' }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" onchange="this.form.action=this.value"><option value="#">Job</option>@foreach($jobOptions as $job)<option value="{{ route('automotive.road-tests.store', $job) }}">{{ $job->job_number }} · {{ $job->vehicle?->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="tester_id" class="form-select"><option value="">Tester</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="start_mileage" type="number" min="0" class="form-control" placeholder="Start mileage"></div>
                <div class="col-md-2"><input name="end_mileage" type="number" min="0" class="form-control" placeholder="End mileage"></div>
                <div class="col-md-2"><select name="test_result" class="form-select"><option>Passed</option><option>Failed</option><option>Not Required</option></select></div>
                <div class="col-md-1"><button class="btn btn-success w-100">Save</button></div>
                <div class="col-12"><textarea name="notes" class="form-control" placeholder="Road test notes"></textarea></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($roadTests as $test)
                <div class="auto-row"><div><strong>{{ $test->road_test_number }}</strong><div class="small text-muted">{{ $test->jobCard?->job_number }} · {{ $test->jobCard?->vehicle?->registration_number }}</div></div><span class="auto-pill">{{ $test->test_result }}</span><span>{{ number_format((float) $test->distance, 2) }} km</span></div>
            @endforeach
            {{ $roadTests->links() }}
        </div>

    @elseif(($section ?? '') === 'vehicle-release')
        <div class="auto-card">
            <h2 class="h5">Release vehicle</h2>
            <form method="post" action="{{ $jobOptions->first() ? route('automotive.releases.store', $jobOptions->first()) : '#' }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" onchange="this.form.action=this.value"><option value="#">Job</option>@foreach($jobOptions as $job)<option value="{{ route('automotive.releases.store', $job) }}">{{ $job->job_number }} · {{ $job->vehicle?->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="final_mileage" type="number" min="0" class="form-control" placeholder="Final mileage"></div>
                <div class="col-md-3"><input name="customer_name" class="form-control" placeholder="Receiver / customer"></div>
                <div class="col-md-2"><select name="payment_status" class="form-select"><option>paid</option><option>unpaid</option><option>partial</option></select></div>
                <div class="col-md-1 form-check pt-2"><input name="override_unpaid" value="1" type="checkbox" class="form-check-input" id="release-override"><label class="form-check-label small" for="release-override">Override</label></div>
                <div class="col-md-1"><button class="btn btn-success w-100">Release</button></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($releases as $release)
                <div class="auto-row"><div><strong>{{ $release->release_number }}</strong><div class="small text-muted">{{ $release->jobCard?->job_number }} · {{ $release->jobCard?->vehicle?->registration_number }}</div></div><span class="auto-pill">{{ $release->payment_status }}</span></div>
            @endforeach
            {{ $releases->links() }}
        </div>

    @elseif(($section ?? '') === 'fleet')
        <div class="auto-card">
            <h2 class="h5">Create fleet account</h2>
            <form method="post" action="{{ route('automotive.fleet.store') }}" class="row g-2">
                @csrf
                <div class="col-md-4"><input name="name" class="form-control" placeholder="Fleet name" required></div>
                <div class="col-md-3"><select name="client_id" class="form-select"><option value="">Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input name="credit_terms" class="form-control" placeholder="Credit terms"></div>
                <div class="col-md-2"><button class="btn btn-success w-100">Create Fleet</button></div>
            </form>
        </div>
        <div class="auto-card auto-list">@foreach($fleets as $fleet)<div class="auto-row"><strong>{{ $fleet->fleet_number }} · {{ $fleet->name }}</strong><span class="auto-pill">{{ $fleet->status }}</span></div>@endforeach{{ $fleets->links() }}</div>

    @elseif(($section ?? '') === 'job-costing')
        <div class="auto-card">
            <h2 class="h5">Calculate job costing</h2>
            <form method="post" action="{{ $jobOptions->first() ? route('automotive.job-cards.costing', $jobOptions->first()) : '#' }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" onchange="this.form.action=this.value"><option value="#">Job</option>@foreach($jobOptions as $job)<option value="{{ route('automotive.job-cards.costing', $job) }}">{{ $job->job_number }} · {{ $job->vehicle?->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="parts_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Parts cost"></div>
                <div class="col-md-2"><input name="labour_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Labour cost"></div>
                <div class="col-md-2"><input name="outsourced_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Outsourced"></div>
                <div class="col-md-2"><input name="revenue" type="number" min="0" step="0.01" class="form-control" placeholder="Revenue"></div>
                <div class="col-md-1"><button class="btn btn-success w-100">Save</button></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($jobCosts as $cost)
                <div class="auto-row"><div><strong>{{ $cost->jobCard?->job_number }}</strong><div class="small text-muted">{{ $cost->jobCard?->vehicle?->registration_number }}</div></div><span class="auto-pill">Cost {{ number_format((float) $cost->actual_cost, 2) }}</span><strong>Profit {{ number_format((float) $cost->gross_profit, 2) }}</strong><span>{{ number_format((float) $cost->margin_percentage, 2) }}%</span></div>
            @endforeach
            {{ $jobCosts->links() }}
        </div>

    @elseif(($section ?? '') === 'service-reminders')
        <div class="auto-card">
            <h2 class="h5">Create service reminder</h2>
            <form method="post" action="{{ route('automotive.service-reminders.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="vehicle_id" class="form-select" required><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-3"><input name="type" class="form-control" placeholder="Reminder type" value="Service Due"></div>
                <div class="col-md-2"><input name="due_date" type="date" class="form-control"></div>
                <div class="col-md-2"><input name="due_mileage" type="number" min="0" class="form-control" placeholder="Due mileage"></div>
                <div class="col-md-1"><select name="status" class="form-select"><option>Open</option><option>Done</option></select></div>
                <div class="col-md-1"><button class="btn btn-success w-100">Save</button></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($reminders as $reminder)
                <div class="auto-row"><div><strong>{{ $reminder->reminder_number }}</strong><div class="small text-muted">{{ $reminder->vehicle?->registration_number }} · {{ $reminder->type }}</div></div><span class="auto-pill">{{ $reminder->status }}</span><span>{{ $reminder->due_date?->toDateString() ?? 'No date' }}</span></div>
            @endforeach
            {{ $reminders->links() }}
        </div>

    @elseif(($section ?? '') === 'sales')
        <div class="auto-card">
            <h2 class="h5">Add vehicle stock</h2>
            <form method="post" action="{{ route('automotive.sales.store') }}" class="row g-2">
                @csrf
                <div class="col-md-2"><input name="registration_number" class="form-control" placeholder="Registration"></div>
                <div class="col-md-2"><input name="make" class="form-control" placeholder="Make"></div>
                <div class="col-md-2"><input name="model" class="form-control" placeholder="Model"></div>
                <div class="col-md-2"><input name="purchase_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Cost"></div>
                <div class="col-md-2"><input name="selling_price" type="number" min="0" step="0.01" class="form-control" placeholder="Selling"></div>
                <div class="col-md-2"><button class="btn btn-success w-100">Save Stock</button></div>
            </form>
        </div>
        <div class="auto-card auto-list">@foreach($vehicleSales as $sale)<div class="auto-row"><strong>{{ $sale->stock_number }}</strong><span>{{ trim(($sale->make ?? '').' '.($sale->model ?? '')) }}</span><span class="auto-pill">{{ $sale->status }}</span></div>@endforeach{{ $vehicleSales->links() }}</div>

    @elseif(($section ?? '') === 'specialty')
        <div class="auto-card">
            <h2 class="h5">{{ $specialtyTitle }} record</h2>
            <form method="post" action="{{ route('automotive.specialty.store', $specialtyType) }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="vehicle_id" class="form-select"><option value="">Vehicle</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="job_card_id" class="form-select"><option value="">Job card</option>@foreach($jobOptions as $job)<option value="{{ $job->id }}">{{ $job->job_number }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="payload[reference]" class="form-control" placeholder="Reference"></div>
                <div class="col-md-2"><input name="payload[work_scope]" class="form-control" placeholder="Work scope"></div>
                <div class="col-md-1"><select name="status" class="form-select"><option>Open</option><option>Approved</option><option>Done</option></select></div>
                <div class="col-md-1"><button class="btn btn-success w-100">Save</button></div>
                <div class="col-md-4"><input name="payload[assessment]" class="form-control" placeholder="Assessment"></div>
                <div class="col-md-4"><input name="payload[parts_or_materials]" class="form-control" placeholder="Parts / materials"></div>
                <div class="col-md-4"><input name="payload[cost_estimate]" type="number" min="0" step="0.01" class="form-control" placeholder="Cost estimate"></div>
            </form>
        </div>
        <div class="auto-card auto-list">
            @foreach($records as $record)
                <div class="auto-row"><div><strong>{{ $record->record_number }}</strong><div class="small text-muted">{{ $record->vehicle?->registration_number }} · {{ $record->jobCard?->job_number }}</div></div><span>{{ $record->payload['work_scope'] ?? $record->payload['reference'] ?? $specialtyTitle }}</span><span class="auto-pill">{{ $record->status }}</span></div>
            @endforeach
            {{ $records->links() }}
        </div>

    @elseif(($section ?? '') === 'customer-service')
        <div class="auto-grid-2">
            <div class="auto-card">
                <h2 class="h5">Record feedback</h2>
                <form method="post" action="{{ route('automotive.feedback.store') }}" class="row g-2">@csrf
                    <div class="col-md-5"><select name="client_id" class="form-select"><option value="">Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><input name="rating" type="number" min="1" max="5" class="form-control" placeholder="1-5"></div>
                    <div class="col-md-4"><button class="btn btn-success w-100">Save Feedback</button></div>
                    <div class="col-12"><textarea name="comments" class="form-control" placeholder="Comments"></textarea></div>
                </form>
            </div>
            <div class="auto-card">
                <h2 class="h5">Open complaint</h2>
                <form method="post" action="{{ route('automotive.complaints.store') }}" class="row g-2">@csrf
                    <div class="col-md-5"><select name="client_id" class="form-select"><option value="">Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><input name="priority" class="form-control" placeholder="Priority"></div>
                    <div class="col-md-4"><button class="btn btn-dark w-100">Open Complaint</button></div>
                    <div class="col-12"><textarea name="description" class="form-control" placeholder="Complaint" required></textarea></div>
                </form>
            </div>
        </div>
        <div class="auto-card auto-list">@foreach($complaints as $complaint)<div class="auto-row"><strong>{{ $complaint->complaint_number }}</strong><span>{{ $complaint->description }}</span><span class="auto-pill">{{ $complaint->status }}</span></div>@endforeach</div>

    @elseif(($section ?? '') === 'reports')
        <div class="auto-grid">
            @foreach($summary as $title => $data)
                <div class="auto-card">
                    <div class="auto-kicker">{{ $title }}</div>
                    <div class="auto-list mt-2">
                        @foreach((array) $data as $name => $value)
                            <div class="auto-row py-2"><span>{{ $name }}</span><strong>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</strong></div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="auto-actions">
            <a class="btn btn-outline-dark" href="{{ route('automotive.reports.csv', 'vehicles') }}">Vehicles CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('automotive.reports.csv', 'job-cards') }}">Job Cards CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('automotive.reports.csv', 'parts') }}">Parts CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('automotive.reports.csv', 'estimates') }}">Estimates CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('automotive.reports.csv', 'job-costing') }}">Job Costing CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('automotive.reports.csv', 'service-reminders') }}">Service Reminders CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('automotive.reports.csv', 'specialty') }}">Specialty CSV</a>
        </div>

    @elseif(($section ?? '') === 'mobile')
        <div class="auto-grid">@foreach($mobileActions as $action)<div class="auto-card"><strong>{{ $action }}</strong></div>@endforeach</div>
        <div class="auto-card auto-list">@foreach($myJobs as $job)<div class="auto-row"><strong>{{ $job->job_number }}</strong><span>{{ $job->vehicle?->registration_number }}</span><span class="auto-pill">{{ $job->status }}</span></div>@endforeach</div>
    @endif
</div>
@endsection
