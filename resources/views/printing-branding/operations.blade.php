@extends('layouts.app')
@section('title', 'Printing & Branding - '.$title)

@section('content')
<style>
    .pb-shell{display:grid;gap:16px}
    .pb-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
    .pb-kicker{color:#007a3b;font-size:.76rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}
    .pb-title{font-size:clamp(1.7rem,3vw,2.5rem);font-weight:900;margin:0;color:#050806}
    .pb-card{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:16px;box-shadow:0 12px 28px rgba(15,23,42,.05)}
    .pb-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    .pb-list{display:grid;gap:10px}
    .pb-row{display:flex;justify-content:space-between;align-items:center;gap:12px;border:1px solid #ecedf0;border-radius:10px;padding:12px;background:#fff}
    .pb-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.28rem .65rem;background:#e9fff2;color:#007a3b;font-size:.78rem;font-weight:800}
    .pb-actions{display:flex;gap:8px;flex-wrap:wrap}
    .pb-actions a{border-radius:999px}
    .pb-board-row{display:grid;grid-template-columns:1fr;gap:10px}
    .pb-board-main{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;min-width:0}
    .pb-board-title{min-width:0}
    .pb-board-title strong,.pb-board-title .small{overflow-wrap:anywhere}
    .pb-board-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center}
    .pb-board-form .form-select{height:38px;min-height:38px;padding-top:.375rem;padding-bottom:.375rem}
    .pb-board-form .btn{height:38px;display:inline-flex;align-items:center;justify-content:center;padding-left:14px;padding-right:14px}
    .pb-specifics{border-top:1px solid #edf0f4;margin-top:12px;padding-top:12px}
    .pb-metric{border:1px solid #ecedf0;border-radius:10px;padding:12px;background:#fbfffc}
    @media(max-width:900px){.pb-grid{grid-template-columns:1fr}.pb-row{display:grid}.pb-board-main{display:grid}.pb-board-form{grid-template-columns:1fr}.pb-board-form .btn{width:100%}}
</style>

<div class="pb-shell">
    <div class="pb-head">
        <div>
            <div class="pb-kicker">Printing & Branding</div>
            <h1 class="pb-title">{{ $title }}</h1>
            <p class="text-muted mb-0" style="max-width:820px">{{ $description }}</p>
        </div>
        <div class="pb-actions">
            <a class="btn btn-dark btn-sm" href="{{ route('printing-branding.dashboard') }}">Dashboard</a>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('printing-branding.jobs') }}">Jobs</a>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('printing-branding.reports') }}">Reports</a>
        </div>
    </div>

    @if(session('success') || session('status'))
        <div class="alert alert-success">{{ session('success') ?: session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if(($section ?? '') === 'estimates')
        <div class="pb-card">
            <h2 class="h5">New estimate</h2>
            <form method="post" action="{{ route('printing-branding.estimates.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="client_id" class="form-select" required><option value="">Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input name="product_name" class="form-control" placeholder="Product" required></div>
                <div class="col-md-2"><input name="quantity" type="number" min="1" step="1" class="form-control" placeholder="Qty" required></div>
                <div class="col-md-2"><input name="material_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Material cost"></div>
                <div class="col-md-2"><input name="markup" type="number" min="0" step="0.01" class="form-control" placeholder="Markup %"></div>
                <div class="col-12"><button class="btn btn-success">Create Estimate</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($estimates as $estimate)
                <div class="pb-row">
                    <div><strong>{{ $estimate->estimate_number }}</strong><div class="text-muted small">{{ $estimate->client?->name }} · {{ $estimate->product_name }}</div></div>
                    <div><span class="pb-pill">{{ $estimate->status }}</span></div>
                    <strong>{{ number_format((float) $estimate->selling_price, 2) }}</strong>
                    <form method="post" action="{{ route('printing-branding.estimates.convert', $estimate) }}">@csrf<button class="btn btn-sm btn-outline-dark">Convert</button></form>
                </div>
            @endforeach
            {{ $estimates->links() }}
        </div>
    @elseif(($section ?? '') === 'jobs')
        <div class="pb-card">
            <h2 class="h5">New production job</h2>
            <form method="post" action="{{ route('printing-branding.jobs.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="client_id" class="form-select" required><option value="">Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input name="product_name" class="form-control" placeholder="Product" required></div>
                <div class="col-md-2"><input id="job-quantity" name="quantity" type="number" min="1" step="1" class="form-control" placeholder="Qty" required></div>
                <div class="col-md-2"><input name="delivery_date" type="date" class="form-control"></div>
                <div class="col-md-2"><select name="status" class="form-select"><option>Draft</option><option>Approved</option><option>Queued</option></select></div>
                <div class="col-12 pb-specifics">
                    <div class="pb-kicker mb-2">Job specifics</div>
                    <div class="row g-2">
                        <div class="col-md-2"><input id="job-spec-quantity" name="specifications[Quantity]" class="form-control" placeholder="Spec quantity"></div>
                        <div class="col-md-3"><input name="specifications[Dimensions]" class="form-control" placeholder="Dimensions e.g. 90 x 50 mm"></div>
                        <div class="col-md-2"><input name="specifications[Material]" class="form-control" placeholder="Material"></div>
                        <div class="col-md-2"><input name="specifications[Print Method]" class="form-control" placeholder="Print method"></div>
                        <div class="col-md-1"><input name="specifications[Colors]" class="form-control" placeholder="Colors"></div>
                        <div class="col-md-1"><input name="specifications[Sides]" class="form-control" placeholder="Sides"></div>
                        <div class="col-md-1"><input name="specifications[Finishing]" class="form-control" placeholder="Finish"></div>
                    </div>
                </div>
                <div class="col-12"><button class="btn btn-success">Create Job</button></div>
            </form>
        </div>
        <div class="pb-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div>
                    <h2 class="h5 mb-0">Add client</h2>
                    <div class="text-muted small">Creates a shared CRM client and a Printing & Branding profile.</div>
                </div>
                <a class="btn btn-sm btn-outline-dark" href="{{ route('clients.index') }}">Open CRM</a>
            </div>
            <form method="post" action="{{ route('printing-branding.clients.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><input name="name" class="form-control" placeholder="Client name" required></div>
                <div class="col-md-2">
                    <select name="client_type" class="form-select" required>
                        @foreach($clientTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input name="phone" class="form-control" placeholder="Phone"></div>
                <div class="col-md-2"><input name="email" type="email" class="form-control" placeholder="Email"></div>
                <div class="col-md-3"><input name="company_name" class="form-control" placeholder="Company / organization"></div>
                <div class="col-md-3"><input name="lead_source" class="form-control" placeholder="Lead source"></div>
                <div class="col-md-3"><input name="print_frequency" class="form-control" placeholder="Print frequency"></div>
                <div class="col-md-2"><input name="price_tier" class="form-control" placeholder="Price tier" value="Standard"></div>
                <div class="col-md-2"><input name="credit_limit" type="number" min="0" step="0.01" class="form-control" placeholder="Credit limit"></div>
                <div class="col-md-2"><button class="btn btn-dark w-100">Add Client</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($jobs as $job)
                <div class="pb-row">
                    <div><strong>{{ $job->job_number }}</strong><div class="text-muted small">{{ $job->client?->name }} · {{ $job->product_name }}</div></div>
                    <span class="pb-pill">{{ $job->status }}</span>
                    <div class="small text-muted">{{ $job->delivery_date?->format('d M Y') ?? 'No due date' }}</div>
                    <div class="pb-actions">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('printing-branding.tickets.show', $job) }}">Ticket</a>
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('printing-branding.jobs.mobile', $job) }}">Mobile</a>
                    </div>
                </div>
            @endforeach
            {{ $jobs->links() }}
        </div>
    @elseif(($section ?? '') === 'board')
        <div class="pb-grid">
            @foreach($board as $column => $jobs)
                <div class="pb-card">
                    <h2 class="h6">{{ $column }}</h2>
                    <div class="pb-list">
                        @forelse($jobs as $job)
                            <div class="pb-row pb-board-row">
                                <div class="pb-board-main">
                                    <div class="pb-board-title"><strong>{{ $job->job_number }}</strong><div class="text-muted small">{{ $job->client?->name }} · {{ $job->product_name }}</div></div>
                                    <span class="pb-pill">{{ $job->priority }}</span>
                                </div>
                                <form method="post" action="{{ route('printing-branding.jobs.status', $job) }}" class="pb-board-form">
                                    @csrf
                                    <select name="status" class="form-select form-select-sm">
                                        @foreach(['Awaiting Artwork','Awaiting Approval','Approved','Queued','In Production','Printing','Finishing','Quality Control','Ready for Dispatch','Completed','On Hold'] as $status)
                                            <option value="{{ $status }}" @selected($job->status === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-dark">Move</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-muted small">No jobs.</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @elseif(($section ?? '') === 'tickets')
        <div class="pb-card pb-list">
            @foreach($jobs as $job)
                <div class="pb-row">
                    <div><strong>{{ $job->ticket?->ticket_number ?? 'No ticket' }}</strong><div class="text-muted small">{{ $job->job_number }} · {{ $job->client?->name }} · Job: {{ $job->status ?: '-' }}</div></div>
                    <span class="pb-pill">{{ $job->ticket?->barcode ?? $job->job_number }}</span>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('printing-branding.tickets.show', $job) }}">View</a>
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('printing-branding.tickets.pdf', $job) }}" target="_blank" rel="noopener">Open PDF</a>
                        <a class="btn btn-sm btn-outline-warning" href="{{ route('printing-branding.tickets.download', $job) }}">Download</a>
                    </div>
                </div>
            @endforeach
            {{ $jobs->links() }}
        </div>
    @elseif(($section ?? '') === 'artwork')
        <div class="pb-card">
            <h2 class="h5">Upload artwork version</h2>
            <form method="post" action="{{ route('printing-branding.artwork.store') }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-4"><select name="job_id" class="form-select" required><option value="">Job</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_number }} - {{ $job->product_name }}</option>@endforeach</select></div>
                <div class="col-md-4"><input type="file" name="file" class="form-control"></div>
                <div class="col-md-4"><input name="revision_notes" class="form-control" placeholder="Revision notes"></div>
                <div class="col-12"><button class="btn btn-success">Upload Artwork</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($artworks as $artwork)
                <div class="pb-row">
                    <div><strong>{{ $artwork->artwork_number }} v{{ $artwork->version }}</strong><div class="text-muted small">{{ $artwork->client?->name }} · {{ $artwork->job?->job_number }}</div></div>
                    <span class="pb-pill">{{ $artwork->status }}</span>
                    <form method="post" action="{{ route('printing-branding.artwork.proof.send', $artwork) }}">@csrf<button class="btn btn-sm btn-outline-dark">Send Proof</button></form>
                </div>
            @endforeach
            {{ $artworks->links() }}
        </div>
    @elseif(($section ?? '') === 'materials')
        <div class="pb-card">
            <h2 class="h5">Add material</h2>
            <form method="post" action="{{ route('printing-branding.materials.store') }}" class="row g-2">
                @csrf
                <div class="col-md-2"><input name="material_code" class="form-control" placeholder="Code" required></div>
                <div class="col-md-3"><input name="name" class="form-control" placeholder="Material name" required></div>
                <div class="col-md-2"><select name="category" class="form-select" required>@foreach(['Paper','Vinyl','Banner Material','Fabric','T-Shirts','Hoodies','Polos','Caps','Ink','Toner','DTF Film','DTF Powder','Sublimation Paper','Heat Transfer Vinyl','Embroidery Thread','Lamination Film','Boards','Acrylic','Perspex','Aluminium','PVC','Packaging Materials'] as $category)<option>{{ $category }}</option>@endforeach</select></div>
                <div class="col-md-1"><input name="unit" class="form-control" placeholder="Unit" value="pcs"></div>
                <div class="col-md-1"><input name="stock_quantity" type="number" min="0" step="0.001" class="form-control" placeholder="Stock"></div>
                <div class="col-md-1"><input name="unit_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Cost"></div>
                <div class="col-md-1"><input name="reorder_level" type="number" min="0" step="0.001" class="form-control" placeholder="Reorder"></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Add</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($materials as $material)
                <div class="pb-row">
                    <div><strong>{{ $material->material_code }}</strong><div class="text-muted small">{{ $material->name }} · {{ $material->category }}</div></div>
                    <span class="pb-pill">{{ number_format((float) $material->stock_quantity, 3) }} {{ $material->unit }}</span>
                    <strong>{{ number_format((float) $material->unit_cost, 2) }}</strong>
                </div>
            @endforeach
            {{ $materials->links() }}
        </div>
        <div class="pb-card">
            <h2 class="h5">Material reservations</h2>
            <div class="pb-list">
                @forelse($reservations as $reservation)
                    <div class="pb-row">
                        <div><strong>{{ $reservation->job?->job_number }}</strong><div class="text-muted small">{{ $reservation->material?->name }} · Reserved {{ number_format((float) $reservation->reserved_quantity, 3) }}</div></div>
                        <span class="pb-pill">{{ $reservation->status }}</span>
                        <form method="post" action="{{ route('printing-branding.materials.consume', $reservation) }}" class="d-flex gap-2">
                            @csrf
                            <input name="quantity" type="number" min="0.001" step="0.001" class="form-control form-control-sm" placeholder="Use qty">
                            <button class="btn btn-sm btn-outline-dark">Consume</button>
                        </form>
                    </div>
                @empty
                    <div class="text-muted">No reserved materials yet.</div>
                @endforelse
            </div>
        </div>
    @elseif(($section ?? '') === 'schedule')
        <div class="pb-card">
            <h2 class="h5">Schedule production</h2>
            <form method="post" action="{{ route('printing-branding.schedule.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="job_id" class="form-select" required><option value="">Job</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_number }} - {{ $job->product_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="machine_id" class="form-select"><option value="">Machine</option>@foreach($machines as $machine)<option value="{{ $machine->id }}">{{ $machine->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="staff_id" class="form-select"><option value="">Staff</option>@foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="starts_at" type="datetime-local" class="form-control" required></div>
                <div class="col-md-2"><input name="ends_at" type="datetime-local" class="form-control" required></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Book</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($schedules as $schedule)
                <div class="pb-row">
                    <div><strong>{{ $schedule->job?->job_number }}</strong><div class="text-muted small">{{ $schedule->view_type }} · Job: {{ $schedule->job?->status ?: '-' }}</div></div>
                    <span>{{ $schedule->starts_at?->format('d M H:i') }} - {{ $schedule->ends_at?->format('H:i') }}</span>
                    <span class="pb-pill">{{ $schedule->status }}</span>
                </div>
            @endforeach
            {{ $schedules->links() }}
        </div>
    @elseif(($section ?? '') === 'production')
        <div class="pb-card">
            <h2 class="h5">Record production stage</h2>
            <form method="post" action="{{ route('printing-branding.operations.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="job_id" class="form-select" required><option value="">Job</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_number }} - {{ $job->product_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="stage" class="form-select" required>@foreach(['Prepress','Printing','Cutting','Lamination','Binding','Folding','Creasing','Embroidery','Heat Press','Mounting','Signage Fabrication','Packaging','Quality Check','Dispatch'] as $stage)<option>{{ $stage }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="operator_id" class="form-select"><option value="">Operator</option>@foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="machine_id" class="form-select"><option value="">Machine</option>@foreach($machines as $machine)<option value="{{ $machine->id }}">{{ $machine->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><input name="quantity_produced" type="number" min="0" step="0.001" class="form-control" placeholder="Good"></div>
                <div class="col-md-1"><input name="quantity_rejected" type="number" min="0" step="0.001" class="form-control" placeholder="Reject"></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Add</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($operations as $operation)
                <div class="pb-row">
                    <div><strong>{{ $operation->stage }}</strong><div class="text-muted small">{{ $operation->job?->job_number }} · Job: {{ $operation->job?->status ?: '-' }} · Produced {{ number_format((float) $operation->quantity_produced, 3) }}</div></div>
                    <span class="pb-pill">{{ $operation->status }}</span>
                    <div class="pb-actions">
                        @foreach(['start' => 'Start', 'pause' => 'Pause', 'complete' => 'Complete'] as $action => $label)
                            <form method="post" action="{{ route('printing-branding.operations.update', $operation) }}">@csrf<input type="hidden" name="action" value="{{ $action }}"><button class="btn btn-sm btn-outline-dark">{{ $label }}</button></form>
                        @endforeach
                    </div>
                </div>
            @endforeach
            {{ $operations->links() }}
        </div>
    @elseif(($section ?? '') === 'machines')
        <div class="pb-card">
            <h2 class="h5">Add machine</h2>
            <form method="post" action="{{ route('printing-branding.machines.store') }}" class="row g-2">
                @csrf
                <div class="col-md-2"><input name="machine_code" class="form-control" placeholder="Code" required></div>
                <div class="col-md-3"><input name="name" class="form-control" placeholder="Machine name" required></div>
                <div class="col-md-2"><select name="machine_type" class="form-select" required>@foreach(['Digital Printer','Offset Machine','DTF Printer','Sublimation Printer','Embroidery Machine','Large Format Printer','UV Printer','Laminator','Guillotine','Plotter','Heat Press','Binding Machine'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="location" class="form-control" placeholder="Location"></div>
                <div class="col-md-1"><input name="cost_per_hour" type="number" min="0" step="0.01" class="form-control" placeholder="Cost/hr"></div>
                <div class="col-md-1"><select name="status" class="form-select"><option>Available</option><option>In Use</option><option>Maintenance</option><option>Breakdown</option><option>Offline</option><option>Retired</option></select></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Add</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($machines as $machine)
                <div class="pb-row">
                    <div><strong>{{ $machine->machine_code }} · {{ $machine->name }}</strong><div class="text-muted small">{{ $machine->machine_type }} · {{ $machine->location ?: 'No location' }}</div></div>
                    <span class="pb-pill">{{ $machine->status }}</span>
                    <form method="post" action="{{ route('printing-branding.machines.maintenance.store', $machine) }}" class="d-flex gap-2">
                        @csrf
                        <input name="maintenance_type" class="form-control form-control-sm" placeholder="Service type" required>
                        <input name="service_date" type="date" class="form-control form-control-sm" required>
                        <input name="next_service_date" type="date" class="form-control form-control-sm">
                        <button class="btn btn-sm btn-outline-dark">Maintain</button>
                    </form>
                </div>
            @endforeach
            {{ $machines->links() }}
        </div>
    @elseif(($section ?? '') === 'quality')
        <div class="pb-card">
            <h2 class="h5">Quality inspection</h2>
            <form method="post" action="{{ route('printing-branding.quality') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="job_id" class="form-select" required><option value="">Job</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_number }} - {{ $job->product_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="inspector_id" class="form-select"><option value="">Inspector</option>@foreach($inspectors as $inspector)<option value="{{ $inspector->id }}">{{ $inspector->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="result" class="form-select" required><option>Pass</option><option>Conditional Pass</option><option>Reject</option><option>Reprint Required</option></select></div>
                <div class="col-md-2"><input name="rejected_quantity" type="number" min="0" step="0.001" class="form-control" placeholder="Rejected qty"></div>
                <div class="col-md-2"><input name="reason" class="form-control" placeholder="Reason"></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Save</button></div>
                <div class="col-12"><input name="notes" class="form-control" placeholder="Inspection notes"></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($checks as $check)
                <div class="pb-row">
                    <div><strong>{{ $check->job?->job_number }}</strong><div class="text-muted small">{{ $check->reason ?: 'Inspection' }} · Job: {{ $check->job?->status ?: '-' }}</div></div>
                    <span class="pb-pill">{{ $check->result }}</span>
                    <span>{{ $check->inspection_date?->format('d M Y H:i') }}</span>
                </div>
            @endforeach
            {{ $checks->links() }}
        </div>
    @elseif(($section ?? '') === 'waste')
        <div class="pb-card">
            <h2 class="h5">Record waste</h2>
            <form method="post" action="{{ route('printing-branding.waste') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="job_id" class="form-select"><option value="">Job</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_number }} - {{ $job->product_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="material_id" class="form-select"><option value="">Material</option>@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="waste_type" class="form-select" required>@foreach(['Paper Waste','Ink Waste','Garment Waste','Vinyl Waste','Banner Waste','Print Reject','Setup Waste','Machine Error'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
                <div class="col-md-1"><input name="quantity" type="number" min="0" step="0.001" class="form-control" placeholder="Qty" required></div>
                <div class="col-md-1"><input name="cost" type="number" min="0" step="0.01" class="form-control" placeholder="Cost"></div>
                <div class="col-md-2"><input name="reason" class="form-control" placeholder="Reason"></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Save</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($wastes as $waste)
                <div class="pb-row">
                    <div><strong>{{ $waste->waste_type }}</strong><div class="text-muted small">{{ $waste->job?->job_number }} · {{ $waste->reason }}</div></div>
                    <span class="pb-pill">{{ number_format((float) $waste->quantity, 3) }}</span>
                    <strong>{{ number_format((float) $waste->cost, 2) }}</strong>
                </div>
            @endforeach
            {{ $wastes->links() }}
        </div>
    @elseif(($section ?? '') === 'dispatch')
        <div class="pb-card">
            <h2 class="h5">Create dispatch</h2>
            <form method="post" action="{{ route('printing-branding.dispatch.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="job_id" class="form-select" required><option value="">Job</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_number }} - {{ $job->client?->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="status" class="form-select"><option>Waiting</option><option>Packed</option><option>Ready</option><option>Out for Delivery</option><option>Delivered</option><option>Collected</option><option>Failed Delivery</option></select></div>
                <div class="col-md-2"><input name="dispatch_date" type="date" class="form-control"></div>
                <div class="col-md-2"><input name="delivery_date" type="date" class="form-control"></div>
                <div class="col-md-2"><input name="receiver_name" class="form-control" placeholder="Receiver"></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Save</button></div>
                <div class="col-12"><input name="delivery_address" class="form-control" placeholder="Delivery address"></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($dispatches as $dispatch)
                <div class="pb-row">
                    <div><strong>{{ $dispatch->dispatch_number }}</strong><div class="text-muted small">{{ $dispatch->job?->job_number }} · Job: {{ $dispatch->job?->status ?: '-' }} · {{ $dispatch->delivery_address }}</div></div>
                    <span class="pb-pill">{{ $dispatch->status }}</span>
                    <form method="post" action="{{ route('printing-branding.delivery-notes.store', $dispatch) }}">@csrf<button class="btn btn-sm btn-outline-dark">Delivery Note</button></form>
                </div>
            @endforeach
            {{ $dispatches->links() }}
        </div>
    @elseif(($section ?? '') === 'outsourcing')
        <div class="pb-card">
            <h2 class="h5">Outsource service</h2>
            <form method="post" action="{{ route('printing-branding.outsourcing.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="job_id" class="form-select" required><option value="">Job</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->job_number }} - {{ $job->product_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="vendor_id" class="form-select"><option value="">Vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="service" class="form-control" placeholder="Service" required></div>
                <div class="col-md-1"><input name="quantity" type="number" min="0.001" step="0.001" class="form-control" placeholder="Qty" required></div>
                <div class="col-md-1"><input name="cost" type="number" min="0" step="0.01" class="form-control" placeholder="Cost"></div>
                <div class="col-md-2"><input name="expected_completion" type="date" class="form-control"></div>
                <div class="col-md-1"><button class="btn btn-dark w-100">Save</button></div>
            </form>
        </div>
        <div class="pb-card pb-list">
            @foreach($orders as $order)
                <div class="pb-row">
                    <div><strong>{{ $order->service }}</strong><div class="text-muted small">{{ $order->job?->job_number }} · Due {{ $order->expected_completion?->format('d M Y') ?? 'TBD' }}</div></div>
                    <span class="pb-pill">{{ $order->delivery_status }}</span>
                    <strong>{{ number_format((float) $order->cost, 2) }}</strong>
                </div>
            @endforeach
            {{ $orders->links() }}
        </div>
    @elseif(($section ?? '') === 'costing')
        <div class="pb-card pb-list">
            @foreach($jobs as $job)
                <div class="pb-row">
                    <div><strong>{{ $job->job_number }}</strong><div class="text-muted small">{{ $job->client?->name }} · {{ $job->product_name }}</div></div>
                    <span class="pb-pill">Margin {{ number_format((float) $job->cost?->margin_percent, 2) }}%</span>
                    <form method="post" action="{{ route('printing-branding.jobs.costing', $job) }}" class="row g-1" style="max-width:680px">
                        @csrf
                        <div class="col"><input name="actual_material_cost" type="number" min="0" step="0.01" class="form-control form-control-sm" placeholder="Material"></div>
                        <div class="col"><input name="machine_cost" type="number" min="0" step="0.01" class="form-control form-control-sm" placeholder="Machine"></div>
                        <div class="col"><input name="labor_cost" type="number" min="0" step="0.01" class="form-control form-control-sm" placeholder="Labor"></div>
                        <div class="col"><input name="selling_price" type="number" min="0" step="0.01" class="form-control form-control-sm" placeholder="Selling"></div>
                        <div class="col-auto"><button class="btn btn-sm btn-outline-dark">Cost</button></div>
                    </form>
                    <form method="post" action="{{ route('printing-branding.jobs.invoice', $job) }}">@csrf<button class="btn btn-sm btn-dark">Invoice</button></form>
                </div>
            @endforeach
            {{ $jobs->links() }}
        </div>
    @elseif(($section ?? '') === 'reports')
        <div class="pb-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-0">Daily production</h2>
                    <div class="text-muted small">{{ $dailyProduction['date']->format('d M Y') }}</div>
                </div>
                <form method="get" action="{{ route('printing-branding.reports') }}" class="d-flex gap-2">
                    <input name="date" type="date" class="form-control form-control-sm" value="{{ $dailyProduction['date']->toDateString() }}">
                    <button class="btn btn-sm btn-dark">View</button>
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('printing-branding.reports.csv', ['type' => 'daily-production', 'date' => $dailyProduction['date']->toDateString()]) }}">CSV</a>
                </form>
            </div>
            <div class="pb-grid mb-3">
                @foreach($dailyProduction['metrics'] as $label => $value)
                    <div class="pb-metric">
                        <div class="pb-kicker">{{ $label }}</div>
                        <strong class="fs-4">
                            @if(is_numeric($value))
                                {{ str_contains($label, 'Rate') ? number_format((float) $value, 2).'%' : number_format((float) $value, str_contains($label, 'Cost') ? 2 : 0) }}
                            @else
                                {{ $value }}
                            @endif
                        </strong>
                    </div>
                @endforeach
            </div>
            <div class="pb-grid">
                <div class="pb-card">
                    <h3 class="h6">Jobs due by status</h3>
                    <div class="pb-list">
                        @forelse($dailyProduction['status_breakdown'] as $status => $count)
                            <div class="pb-row"><span>{{ $status }}</span><strong>{{ $count }}</strong></div>
                        @empty
                            <div class="text-muted small">No jobs due.</div>
                        @endforelse
                    </div>
                </div>
                <div class="pb-card">
                    <h3 class="h6">Production stages worked</h3>
                    <div class="pb-list">
                        @forelse($dailyProduction['stage_breakdown'] as $stage => $count)
                            <div class="pb-row"><span>{{ $stage }}</span><strong>{{ $count }}</strong></div>
                        @empty
                            <div class="text-muted small">No stage activity.</div>
                        @endforelse
                    </div>
                </div>
                <div class="pb-card">
                    <h3 class="h6">Quality and waste</h3>
                    <div class="pb-list">
                        <div class="pb-row"><span>Quality checks</span><strong>{{ $dailyProduction['quality_checks']->count() }}</strong></div>
                        <div class="pb-row"><span>Waste entries</span><strong>{{ $dailyProduction['wastes']->count() }}</strong></div>
                        <div class="pb-row"><span>Completed jobs</span><strong>{{ $dailyProduction['completed_jobs']->count() }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="pb-card">
            <h2 class="h5">Day production activity</h2>
            <div class="pb-list">
                @forelse($dailyProduction['operations'] as $operation)
                    <div class="pb-row">
                        <div>
                            <strong>{{ $operation->job?->job_number }} · {{ $operation->stage }}</strong>
                            <div class="text-muted small">{{ $operation->job?->client?->name }} · {{ $operation->job?->product_name }}</div>
                        </div>
                        <span class="pb-pill">{{ $operation->status }}</span>
                        <div class="small">Produced {{ number_format((float) $operation->quantity_produced, 3) }}</div>
                        <div class="small text-muted">Rejected {{ number_format((float) $operation->quantity_rejected, 3) }}</div>
                    </div>
                @empty
                    <div class="text-muted">No production stages recorded for this day.</div>
                @endforelse
            </div>
        </div>
        <div class="pb-card">
            <h2 class="h5">Jobs due today</h2>
            <div class="pb-list">
                @forelse($dailyProduction['jobs_due'] as $job)
                    <div class="pb-row">
                        <div>
                            <strong>{{ $job->job_number }}</strong>
                            <div class="text-muted small">{{ $job->client?->name }} · {{ $job->product_name }}</div>
                        </div>
                        <span class="pb-pill">{{ $job->status }}</span>
                        <div class="small">{{ number_format((float) $job->quantity, 3) }} units</div>
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('printing-branding.tickets.show', $job) }}">Ticket</a>
                    </div>
                @empty
                    <div class="text-muted">No jobs due for this day.</div>
                @endforelse
            </div>
        </div>
        <div class="pb-grid">
            @foreach($reports as $report)
                <div class="pb-card"><strong>{{ $report }}</strong></div>
            @endforeach
        </div>
        <div class="pb-card">
            <h2 class="h5">Exports</h2>
            <div class="pb-actions">
                @foreach(['jobs' => 'Jobs CSV', 'estimates' => 'Estimates CSV', 'waste' => 'Waste CSV', 'daily-production' => 'Daily Production CSV'] as $type => $label)
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('printing-branding.reports.csv', $type === 'daily-production' ? ['type' => $type, 'date' => $dailyProduction['date']->toDateString()] : $type) }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    @elseif(($section ?? '') === 'settings')
        @php($settings = $companySettings ?? null)
        @if($settings)
            <div class="pb-card">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <div class="pb-kicker">Profile, invoices & documents</div>
                        <h2 class="h5 mb-1">Business identity and invoice appearance</h2>
                        <p class="text-muted mb-0">These details print on invoices, quotations, receipts and downloadable PDFs for this profile.</p>
                    </div>
                    @if($settings->logoUrl())
                        <img src="{{ $settings->logoUrl() }}" alt="{{ $settings->company_name }} logo" style="width:74px;height:74px;object-fit:contain;border:1px solid #e5e7eb;border-radius:10px;background:#fff;padding:6px">
                    @endif
                </div>
                <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-4"><label class="form-label">Company name</label><input class="form-control" name="company_name" value="{{ old('company_name',$settings->company_name) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone',$settings->phone) }}"></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email',$settings->email) }}"></div>
                    <div class="col-md-4"><label class="form-label">Website</label><input class="form-control" name="website" value="{{ old('website',$settings->website) }}"></div>
                    <div class="col-md-4"><label class="form-label">Location</label><input class="form-control" name="location" value="{{ old('location',$settings->location) }}" placeholder="City, town, branch or site"></div>
                    <div class="col-md-2"><label class="form-label">Currency</label><input class="form-control" name="currency_code" maxlength="3" value="{{ old('currency_code',$settings->currency_code ?? 'KES') }}" required></div>
                    <div class="col-md-2"><label class="form-label">Locale</label><input class="form-control" name="locale" value="{{ old('locale',$settings->locale ?? 'en_KE') }}" required></div>
                    <div class="col-md-6"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3">{{ old('address',$settings->address) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Default invoice / quotation terms</label><textarea class="form-control" name="default_terms" rows="3">{{ old('default_terms',$settings->default_terms) }}</textarea></div>
                    <div class="col-md-4"><label class="form-label">Tax name</label><input class="form-control" name="tax_name" value="{{ old('tax_name',$settings->tax_name) }}" placeholder="VAT, GST, optional"></div>
                    <div class="col-md-4"><label class="form-label">Tax rate %</label><input class="form-control" type="number" step="0.01" min="0" name="tax_rate" value="{{ old('tax_rate',$settings->tax_rate) }}"></div>
                    <div class="col-md-4"><label class="form-label">Logo</label><input class="form-control" type="file" name="logo" accept="image/*"></div>
                    <div class="col-md-4">
                        <label class="form-label">Invoice primary color</label>
                        <div class="input-group"><input class="form-control form-control-color" type="color" name="primary_color" value="{{ old('primary_color',$settings->primary_color ?? \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR) }}"><input class="form-control" value="{{ old('primary_color',$settings->primary_color ?? \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR) }}" disabled></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Invoice secondary color</label>
                        <div class="input-group"><input class="form-control form-control-color" type="color" name="secondary_color" value="{{ old('secondary_color',$settings->secondary_color ?? \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR) }}"><input class="form-control" value="{{ old('secondary_color',$settings->secondary_color ?? \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR) }}" disabled></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Invoice accent color</label>
                        <div class="input-group"><input class="form-control form-control-color" type="color" name="accent_color" value="{{ old('accent_color',$settings->accent_color ?? \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR) }}"><input class="form-control" value="{{ old('accent_color',$settings->accent_color ?? \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR) }}" disabled></div>
                    </div>
                    <div class="col-12"><button class="btn btn-dark">Save Profile & Invoice Settings</button></div>
                </form>
            </div>
        @endif

        <div class="pb-grid">
            <div class="pb-card">
                <h2 class="h5">Invoice payment methods</h2>
                <form method="post" action="{{ route('printing-branding.settings.payment-methods.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-7"><input class="form-control" name="name" placeholder="Bank, M-Pesa, Cash" required></div>
                    <div class="col-md-5"><select class="form-select" name="type"><option value="bank">Bank</option><option value="mpesa">M-Pesa</option><option value="cash">Cash</option><option value="custom">Other</option></select></div>
                    <div class="col-12"><textarea class="form-control" name="details" rows="3" placeholder="Account name, account number, Paybill/Till, branch, payment instructions"></textarea></div>
                    <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <span class="form-check-label">Show on invoices</span></label></div>
                    <div class="col-12"><button class="btn btn-outline-dark btn-sm">Add Payment Method</button></div>
                </form>
                <div class="pb-list mt-3">
                    @forelse(($paymentMethods ?? collect()) as $method)
                        <div class="pb-row">
                            <div><strong>{{ $method->name }}</strong><div class="text-muted small">{{ $method->type }} · {{ $method->details }}</div></div>
                            <form method="post" action="{{ route('printing-branding.settings.payment-methods.destroy',$method) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </div>
                        <details class="mt-2">
                            <summary class="btn btn-sm btn-outline-dark">Edit</summary>
                            <form method="post" action="{{ route('printing-branding.settings.payment-methods.update',$method) }}" class="row g-2 mt-2">
                                @csrf @method('PUT')
                                <div class="col-md-7"><input class="form-control" name="name" value="{{ old('name',$method->name) }}" required></div>
                                <div class="col-md-5"><select class="form-select" name="type">@foreach(['bank'=>'Bank','mpesa'=>'M-Pesa','cash'=>'Cash','custom'=>'Other'] as $value=>$label)<option value="{{ $value }}" @selected(old('type',$method->type)===$value)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-12"><textarea class="form-control" name="details" rows="3">{{ old('details',$method->details) }}</textarea></div>
                                <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$method->is_active))> <span class="form-check-label">Show on invoices</span></label></div>
                                <div class="col-12"><button class="btn btn-dark btn-sm">Update Payment Method</button></div>
                            </form>
                        </details>
                    @empty
                        <div class="text-muted">No payment methods yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="pb-card">
                <h2 class="h5">Signatures & stamps</h2>
                <form method="post" action="{{ route('printing-branding.settings.signatories.store') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-md-6"><input class="form-control" name="name" value="{{ old('name') }}" placeholder="Full name"></div>
                    <div class="col-md-6"><input class="form-control" name="title" value="{{ old('title') }}" placeholder="Title"></div>
                    <div class="col-md-6"><label class="form-label">Signature</label><input class="form-control" type="file" name="signature" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/*"></div>
                    <div class="col-md-6"><label class="form-label">Stamp</label><input class="form-control" type="file" name="stamp" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/*"></div>
                    <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1"> <span class="form-check-label">Default on documents</span></label></div>
                    <div class="col-12"><button class="btn btn-outline-dark btn-sm">Add Signatory</button></div>
                </form>
                <div class="pb-list mt-3">
                    @forelse(($signatories ?? collect()) as $sig)
                        <div class="pb-row">
                            <div class="d-flex align-items-center gap-2">
                                @if($sig->signatureUrl())<img src="{{ $sig->signatureUrl() }}" alt="Signature" style="max-height:34px;max-width:80px;object-fit:contain">@endif
                                @if($sig->stampUrl())<img src="{{ $sig->stampUrl() }}" alt="Stamp" style="max-height:42px;max-width:74px;object-fit:contain">@endif
                                <div><strong>{{ $sig->name }}</strong><div class="text-muted small">{{ $sig->title }} @if($sig->is_default) · Default @endif</div></div>
                            </div>
                            <form method="post" action="{{ route('printing-branding.settings.signatories.destroy',$sig) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </div>
                        <details class="mt-2">
                            <summary class="btn btn-sm btn-outline-dark">Edit</summary>
                            <form method="post" action="{{ route('printing-branding.settings.signatories.update',$sig) }}" enctype="multipart/form-data" class="row g-2 mt-2">
                                @csrf @method('PUT')
                                <div class="col-md-6"><input class="form-control" name="name" value="{{ old('name',$sig->name) }}" required></div>
                                <div class="col-md-6"><input class="form-control" name="title" value="{{ old('title',$sig->title) }}" placeholder="Title"></div>
                                <div class="col-md-6"><label class="form-label">Replace signature</label><input class="form-control" type="file" name="signature" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/*"></div>
                                <div class="col-md-6"><label class="form-label">Replace stamp</label><input class="form-control" type="file" name="stamp" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/*"></div>
                                <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked(old('is_default',$sig->is_default))> <span class="form-check-label">Default on documents</span></label></div>
                                <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$sig->is_active))> <span class="form-check-label">Active</span></label></div>
                                <div class="col-12"><button class="btn btn-dark btn-sm">Update Signatory</button></div>
                            </form>
                        </details>
                    @empty
                        <div class="text-muted">No signatories or stamps yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="pb-card">
                <h2 class="h5">Reusable terms</h2>
                <form method="post" action="{{ route('printing-branding.settings.terms.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><input class="form-control" name="title" placeholder="Title" required></div>
                    <div class="col-12"><textarea class="form-control" name="content" rows="4" placeholder="Terms content" required></textarea></div>
                    <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1"> <span class="form-check-label">Use as default</span></label></div>
                    <div class="col-12"><button class="btn btn-outline-dark btn-sm">Add Terms</button></div>
                </form>
                <div class="pb-list mt-3">
                    @forelse(($terms ?? collect()) as $term)
                        <div class="pb-row">
                            <div><strong>{{ $term->title }}</strong>@if($term->is_default)<span class="pb-pill ms-1">Default</span>@endif<div class="text-muted small">{{ $term->content }}</div></div>
                            <form method="post" action="{{ route('printing-branding.settings.terms.destroy',$term) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </div>
                        <details class="mt-2">
                            <summary class="btn btn-sm btn-outline-dark">Edit</summary>
                            <form method="post" action="{{ route('printing-branding.settings.terms.update',$term) }}" class="row g-2 mt-2">
                                @csrf @method('PUT')
                                <div class="col-12"><input class="form-control" name="title" value="{{ old('title',$term->title) }}" required></div>
                                <div class="col-12"><textarea class="form-control" name="content" rows="4" required>{{ old('content',$term->content) }}</textarea></div>
                                <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked(old('is_default',$term->is_default))> <span class="form-check-label">Use as default</span></label></div>
                                <div class="col-12"><button class="btn btn-dark btn-sm">Update Terms</button></div>
                            </form>
                        </details>
                    @empty
                        <div class="text-muted">No reusable terms yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="pb-kicker mt-2">Printing production settings</div>
        <div class="pb-grid">
            <div class="pb-card">
                <h2 class="h5">Product template</h2>
                <form method="post" action="{{ route('printing-branding.settings.templates.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><input name="template_code" class="form-control" placeholder="Code" required></div>
                    <div class="col-12"><input name="name" class="form-control" placeholder="Name" required></div>
                    <div class="col-12"><input name="category" class="form-control" placeholder="Category" required></div>
                    <div class="col-12"><input name="specifications[Dimensions]" class="form-control" placeholder="Default dimensions"></div>
                    <div class="col-12"><button class="btn btn-dark">Save Template</button></div>
                </form>
            </div>
            <div class="pb-card">
                <h2 class="h5">Print method</h2>
                <form method="post" action="{{ route('printing-branding.settings.print-methods.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><input name="method_code" class="form-control" placeholder="Code" required></div>
                    <div class="col-12"><input name="name" class="form-control" placeholder="Method name" required></div>
                    <div class="col-6"><input name="setup_cost" type="number" min="0" step="0.01" class="form-control" placeholder="Setup cost"></div>
                    <div class="col-6"><input name="estimated_production_minutes" type="number" min="0" class="form-control" placeholder="Minutes"></div>
                    <div class="col-12"><button class="btn btn-dark">Save Method</button></div>
                </form>
            </div>
            <div class="pb-card">
                <h2 class="h5">Finishing option</h2>
                <form method="post" action="{{ route('printing-branding.settings.finishing.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><input name="option_code" class="form-control" placeholder="Code" required></div>
                    <div class="col-12"><input name="name" class="form-control" placeholder="Finishing name" required></div>
                    <div class="col-6"><input name="cost" type="number" min="0" step="0.01" class="form-control" placeholder="Cost"></div>
                    <div class="col-6"><input name="production_minutes" type="number" min="0" class="form-control" placeholder="Minutes"></div>
                    <div class="col-12"><button class="btn btn-dark">Save Finish</button></div>
                </form>
            </div>
            <div class="pb-card">
                <h2 class="h5">Pricing rule</h2>
                <form method="post" action="{{ route('printing-branding.settings.pricing-rules.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><input name="rule_code" class="form-control" placeholder="Code" required></div>
                    <div class="col-12"><input name="name" class="form-control" placeholder="Rule name" required></div>
                    <div class="col-8"><select name="rule_type" class="form-select"><option>Quantity Pricing</option><option>Customer Pricing</option><option>Corporate Rates</option><option>Reseller Rates</option><option>Bulk Discounts</option><option>Urgent Job Surcharge</option><option>Design Fees</option><option>Setup Fees</option><option>Machine Charges</option><option>Delivery Charges</option><option>Material Markups</option></select></div>
                    <div class="col-4"><input name="rate" type="number" min="0" step="0.01" class="form-control" placeholder="Rate"></div>
                    <div class="col-12"><button class="btn btn-dark">Save Rule</button></div>
                </form>
            </div>
        </div>
        <div class="pb-card">
            <h2 class="h5">Configured records</h2>
            <div class="pb-list">
                @foreach($templates as $template)<div class="pb-row"><strong>{{ $template->name }}</strong><span class="pb-pill">Template</span></div>@endforeach
                @foreach($methods as $method)<div class="pb-row"><strong>{{ $method->name }}</strong><span class="pb-pill">Method</span></div>@endforeach
                @foreach($finishing as $finish)<div class="pb-row"><strong>{{ $finish->name }}</strong><span class="pb-pill">Finishing</span></div>@endforeach
                @foreach($pricingRules as $rule)<div class="pb-row"><strong>{{ $rule->name }}</strong><span class="pb-pill">{{ $rule->rule_type }}</span></div>@endforeach
            </div>
        </div>
    @else
        <div class="pb-card">
            <div class="pb-list">
                @foreach(get_defined_vars() as $name => $value)
                    @if($value instanceof \Illuminate\Contracts\Pagination\Paginator)
                        @foreach($value as $item)
                            <div class="pb-row">
                                <strong>{{ $item->name ?? $item->job_number ?? $item->dispatch_number ?? $item->service ?? $item->id }}</strong>
                                <span class="pb-pill">{{ $item->status ?? $item->result ?? 'Active' }}</span>
                            </div>
                        @endforeach
                        {{ $value->links() }}
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>

@if(($section ?? '') === 'jobs')
    <script>
        const jobQuantity = document.getElementById('job-quantity');
        const jobSpecQuantity = document.getElementById('job-spec-quantity');
        if (jobQuantity && jobSpecQuantity) {
            jobQuantity.addEventListener('input', () => {
                jobSpecQuantity.value = jobQuantity.value;
            });
        }
    </script>
@endif
@endsection
