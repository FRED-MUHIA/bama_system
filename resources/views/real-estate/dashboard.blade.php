@extends('layouts.app')
@section('title','Real Estate')
@section('content')
@php
    $money = fn ($v) => 'KES '.number_format((float) $v, 2);
    $currentSection = request()->query('section', 'dashboard');
    $sectionTabs = [
        'dashboard' => 'portfolio',
        'portfolio' => 'portfolio',
        'properties' => 'properties',
        'listings' => 'sales',
        'units' => 'units',
        'tenants' => 'crm',
        'tenant-archive' => 'crm',
        'offboarding' => 'crm',
        'tenant-alerts' => 'crm',
        'buyers' => 'crm',
        'leases' => 'leases',
        'rental-billing' => 'leases',
        'payments' => 'payments',
        'utilities' => 'utilities',
        'amenities' => 'amenities',
        'tenant-ledger' => 'tenant-ledger',
        'collections' => 'collections',
        'consumption' => 'consumption',
        'statements' => 'tenant-ledger',
        'sales' => 'sales',
        'agents' => 'sales',
        'commissions' => 'sales',
        'maintenance' => 'maintenance',
        'service-requests' => 'maintenance',
        'inspections' => 'records',
        'valuations' => 'records',
        'land' => 'records',
        'development' => 'portfolio',
        'documents' => 'documents',
    ];
    $activeTab = $sectionTabs[$currentSection] ?? 'portfolio';
@endphp
<div class="d-flex justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h5 mb-1">Real Estate Management</h1>
        <p class="text-muted mb-0">Properties, units, tenants, leases, billing, sales, maintenance, valuations, land, and portfolio controls.</p>
    </div>
    <div class="d-flex gap-2"><a class="btn btn-outline-dark" href="{{ route('real-estate.reports.index') }}"><i class="bi bi-bar-chart"></i> Reports</a><a class="btn btn-dark" href="{{ route('finance.index') }}"><i class="bi bi-bank"></i> Finance</a></div>
</div>

<div class="row g-3 mb-4">
@foreach([
    ['Properties',$metrics['properties'],'primary'],['Units',$metrics['units'],'dark'],['Occupancy',$metrics['occupancy_rate'].'%','success'],['Portfolio Value',$money($metrics['portfolio_value']),'info'],
    ['Outstanding Rent',$money($metrics['outstanding_rent']),'warning'],['Utility Revenue',$money($metrics['utility_revenue'] ?? 0),'success'],
    ['Amenity Revenue',$money($metrics['amenity_revenue'] ?? 0),'primary'],['Archived Tenants',($archiveTenants ?? collect())->count(),'dark'],['Open Maintenance',$metrics['open_maintenance'],'danger'],
] as [$label,$value,$color])
    <div class="col-md-4 col-xl-3"><div class="card p-3 h-100"><span class="text-muted small">{{ $label }}</span><strong class="text-{{ $color }}">{{ $value }}</strong></div></div>
@endforeach
</div>

<ul class="nav nav-tabs mb-3">
@foreach(['portfolio'=>'Portfolio','properties'=>'Properties','units'=>'Units','crm'=>'Tenants & Buyers','leases'=>'Leases & Billing','payments'=>'Payments','utilities'=>'Utilities','amenities'=>'Amenities','tenant-ledger'=>'Tenant Ledger','collections'=>'Collections','consumption'=>'Consumption','sales'=>'Sales & Agents','maintenance'=>'Maintenance','records'=>'Inspections & Land','documents'=>'Documents'] as $id=>$label)
    <li class="nav-item"><a class="nav-link {{ $activeTab === $id ? 'active' : '' }}" href="{{ route('real-estate.dashboard', ['section' => $id]) }}">{{ $label }}</a></li>
@endforeach
</ul>

<div class="tab-content">
<div class="tab-pane fade {{ $activeTab === 'portfolio' ? 'show active' : '' }}" id="re-portfolio">
    <div class="card p-3"><h2 class="h6">Portfolio Dashboard</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Property</th><th>Type</th><th>Status</th><th>Branch</th><th>Units</th><th>Listings</th><th>Leases</th><th>Maintenance</th><th>Value</th></tr></thead><tbody>
    @forelse($portfolioRows as $property)<tr><td>{{ $property->property_code }}<small class="d-block">{{ $property->property_name }}</small></td><td>{{ $property->property_type }}</td><td><span class="status-pill">{{ $property->status }}</span></td><td>{{ $property->branch?->name ?? 'Head Office' }}</td><td>{{ $property->units_count }}</td><td>{{ $property->listings_count }}</td><td>{{ $property->leases_count }}</td><td>{{ $property->maintenance_requests_count }}</td><td>{{ $money($property->market_value) }}</td></tr>@empty<tr><td colspan="9" class="text-muted">No properties yet.</td></tr>@endforelse
    </tbody></table></div></div>
    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h6">Development Project</h2>
                <form method="post" action="{{ route('real-estate.records.store','development') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="real_estate_property_id"><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select>
                    <input class="form-control" name="name" placeholder="Project name" required>
                    <input class="form-control" name="phase" placeholder="Phase">
                    <input class="form-control" name="contractor" placeholder="Contractor">
                    <div class="col-6"><input class="form-control" type="number" step="0.01" name="budget" placeholder="Budget"></div>
                    <div class="col-6"><input class="form-control" type="number" step="0.01" name="actual_cost" placeholder="Actual cost"></div>
                    <div class="col-6"><input class="form-control" type="number" min="0" max="100" name="progress_percent" placeholder="Progress %"></div>
                    <div class="col-6"><select class="form-select" name="status"><option>Planning</option><option>Approval</option><option>Construction</option><option>Completed</option></select></div>
                    <button class="btn btn-warning">Save Development</button>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card p-3 h-100"><h2 class="h6">Property Development</h2><table class="table"><thead><tr><th>Project</th><th>Property</th><th>Phase</th><th>Budget</th><th>Progress</th><th>Status</th></tr></thead><tbody>@forelse($developmentProjects as $project)<tr><td>{{ $project->development_number }}<small class="d-block">{{ $project->name }}</small></td><td>{{ $project->property?->property_name }}</td><td>{{ $project->phase }}</td><td>{{ $money($project->budget) }}</td><td>{{ $project->progress_percent }}%</td><td>{{ $project->status }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No development projects yet.</td></tr>@endforelse</tbody></table></div>
        </div>
    </div>
</div>

<div class="tab-pane fade {{ $activeTab === 'properties' ? 'show active' : '' }}" id="re-properties">
    <div class="row g-3"><div class="col-lg-4"><div class="card p-3"><h2 class="h6">New Property</h2><form method="post" action="{{ route('real-estate.properties.store') }}" class="row g-2">@csrf
        <input class="form-control" name="property_code" placeholder="Property code">
        <input class="form-control" name="property_name" placeholder="Property name" required>
        <select class="form-select" name="property_type">@foreach(\Modules\RealEstate\Support\RealEstateValidationRules::$propertyTypes as $type)<option>{{ $type }}</option>@endforeach</select>
        <select class="form-select" name="ownership_type"><option>Owned</option><option>Leased</option><option>Managed</option><option>Joint Venture</option></select>
        <select class="form-select" name="status">@foreach(\Modules\RealEstate\Support\RealEstateValidationRules::$propertyStatuses as $status)<option>{{ $status }}</option>@endforeach</select>
        <select class="form-select" name="branch_id"><option value="">Head Office</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
        <select class="form-select" name="property_manager_id"><option value="">Property manager</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
        <input class="form-control" name="city" placeholder="City"><input class="form-control" name="county_state" placeholder="County / State"><input class="form-control" name="country" placeholder="Country">
        <textarea class="form-control" name="address" placeholder="Address"></textarea>
        <div class="col-6"><input class="form-control" type="date" name="acquisition_date"></div><div class="col-6"><input class="form-control" type="number" step="0.01" name="acquisition_cost" placeholder="Acquisition cost"></div>
        <input class="form-control" type="number" step="0.01" name="market_value" placeholder="Market value">
        <textarea class="form-control" name="description" placeholder="Description"></textarea><button class="btn btn-warning">Save Property</button>
    </form></div></div><div class="col-lg-8"><div class="card p-3"><h2 class="h6">Properties</h2><div class="table-responsive"><table class="table"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Status</th><th>Value</th></tr></thead><tbody>@foreach($properties as $property)<tr><td>{{ $property->property_code }}</td><td>{{ $property->property_name }}</td><td>{{ $property->property_type }}</td><td>{{ $property->status }}</td><td>{{ $money($property->market_value) }}</td></tr>@endforeach</tbody></table></div></div></div></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'units' ? 'show active' : '' }}" id="re-units">
    <div class="row g-3"><div class="col-lg-4"><div class="card p-3"><h2 class="h6">New Unit</h2><form method="post" action="{{ route('real-estate.units.store') }}" class="row g-2">@csrf
        <select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select>
        <input class="form-control" name="unit_number" placeholder="Unit number" required><div class="col-6"><input class="form-control" name="floor" placeholder="Floor"></div><div class="col-6"><input class="form-control" name="block" placeholder="Block"></div>
        <input class="form-control" name="unit_type" placeholder="Unit type"><div class="col-4"><input class="form-control" type="number" name="bedrooms" placeholder="Beds"></div><div class="col-4"><input class="form-control" type="number" name="bathrooms" placeholder="Baths"></div><div class="col-4"><input class="form-control" type="number" step="0.01" name="square_footage" placeholder="Sq ft"></div>
        <select class="form-select" name="occupancy_status">@foreach(\Modules\RealEstate\Support\RealEstateValidationRules::$unitStatuses as $status)<option>{{ $status }}</option>@endforeach</select>
        <div class="col-6"><input class="form-control" type="number" step="0.01" name="rent_amount" placeholder="Rent"></div><div class="col-6"><input class="form-control" type="number" step="0.01" name="sale_price" placeholder="Sale price"></div><button class="btn btn-warning">Save Unit</button>
    </form></div></div><div class="col-lg-8"><div class="card p-3"><h2 class="h6">Units</h2><table class="table"><thead><tr><th>Property</th><th>Unit</th><th>Status</th><th>Rent</th><th>Sale</th></tr></thead><tbody>@foreach($units as $unit)<tr><td>{{ $unit->property?->property_name }}</td><td>{{ $unit->unit_number }}</td><td>{{ $unit->occupancy_status }}</td><td>{{ $money($unit->rent_amount) }}</td><td>{{ $money($unit->sale_price) }}</td></tr>@endforeach</tbody></table></div></div></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'crm' ? 'show active' : '' }}" id="re-crm">
    <div class="row g-3"><div class="col-lg-6"><div class="card p-3"><h2 class="h6">Tenant Profile</h2><form method="post" action="{{ route('real-estate.tenants.store') }}" class="row g-2">@csrf
        <select class="form-select" name="client_id"><option value="">Create CRM client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select><input class="form-control" name="name" placeholder="Full name if new client"><div class="col-6"><input class="form-control" name="phone" placeholder="Phone"></div><div class="col-6"><input class="form-control" name="email" placeholder="Email"></div><input class="form-control" name="id_number" placeholder="ID number"><input class="form-control" name="employer" placeholder="Employer"><input class="form-control" name="emergency_contact" placeholder="Emergency contact"><select class="form-select" name="status"><option>Prospect</option><option>Active</option><option>Notice Given</option><option>Moving Out</option><option>Moved Out</option><option>Archived</option><option>Blacklisted</option></select><select class="form-select" name="real_estate_unit_id"><option value="">Assign vacant unit</option>@foreach($availableUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->property?->property_name }} - {{ $unit->unit_number }} - {{ $money($unit->rent_amount) }}</option>@endforeach</select><div class="col-6"><input class="form-control" type="date" name="lease_start_date" value="{{ now()->toDateString() }}"></div><div class="col-6"><input class="form-control" type="number" step="0.01" name="assignment_rent_amount" placeholder="Rent override"></div><div class="col-6"><input class="form-control" type="number" step="0.01" name="assignment_deposit_amount" placeholder="Deposit"></div><div class="col-6"><select class="form-select" name="assignment_billing_cycle"><option>Monthly</option><option>Quarterly</option><option>Annual</option></select></div><label><input type="checkbox" name="auto_billing" value="1"> Auto billing</label><button class="btn btn-warning">Save Tenant</button>
    </form></div></div><div class="col-lg-6"><div class="card p-3"><h2 class="h6">Buyer / Investor</h2><form method="post" action="{{ route('real-estate.buyers.store') }}" class="row g-2">@csrf
        <select class="form-select" name="client_id"><option value="">Create CRM client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select><input class="form-control" name="name" placeholder="Buyer name if new client"><input class="form-control" name="phone" placeholder="Phone"><input class="form-control" name="email" placeholder="Email"><input class="form-control" type="number" step="0.01" name="budget" placeholder="Budget"><textarea class="form-control" name="preferred_locations" placeholder="Preferred locations"></textarea><textarea class="form-control" name="property_interests" placeholder="Property interests"></textarea><select class="form-select" name="status"><option>Prospect</option><option>Buyer</option><option>Investor</option><option>Inactive</option></select><button class="btn btn-warning">Save Buyer</button>
    </form></div></div></div>
    <div class="row g-3 mt-1"><div class="col-lg-6"><div class="card p-3"><h2 class="h6">Active Tenants</h2>@foreach($tenants as $tenant)@php($currentLease = $tenant->leases->where('status','Active')->sortByDesc('id')->first())<div class="border-top py-2"><div class="d-flex justify-content-between gap-2"><span>{{ $tenant->tenant_number }} · {{ $tenant->client?->name }}<small class="d-block text-muted">{{ $currentLease?->unit ? $currentLease->property?->property_name.' - '.$currentLease->unit?->unit_number : 'No unit assigned' }}</small></span><span>{{ $tenant->status }}</span></div><form method="post" action="{{ route('real-estate.tenants.unit-assignment.store',$tenant) }}" class="row g-1 mt-1">@csrf<div class="col-5"><select class="form-select form-select-sm" name="real_estate_unit_id" required><option value="">Assign vacant unit</option>@foreach($availableUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->property?->property_name }} - {{ $unit->unit_number }}</option>@endforeach</select></div><div class="col-3"><input class="form-control form-control-sm" type="date" name="lease_start_date" value="{{ now()->toDateString() }}"></div><div class="col-2"><input class="form-control form-control-sm" type="number" step="0.01" name="assignment_rent_amount" placeholder="Rent"></div><div class="col-2"><button class="btn btn-sm btn-outline-success w-100" @disabled($availableUnits->isEmpty())>Assign</button></div><input type="hidden" name="assignment_billing_cycle" value="Monthly"></form><form method="post" action="{{ route('real-estate.tenants.notice',$tenant) }}" class="row g-1 mt-1">@csrf<div class="col-5"><input class="form-control form-control-sm" type="date" name="notice_date" value="{{ now()->toDateString() }}"></div><div class="col-5"><input class="form-control form-control-sm" type="date" name="move_out_date"></div><div class="col-2"><button class="btn btn-sm btn-outline-warning w-100">Notice</button></div></form><form method="post" action="{{ route('real-estate.tenants.archive',$tenant) }}" class="mt-1">@csrf<button class="btn btn-sm btn-outline-dark">Archive</button></form></div>@endforeach</div></div><div class="col-lg-6"><div class="card p-3"><h2 class="h6">Buyers</h2>@foreach($buyers as $buyer)<div class="border-top py-2">{{ $buyer->buyer_number }} · {{ $buyer->client?->name }}<span class="float-end">{{ $money($buyer->budget) }}</span></div>@endforeach</div></div></div>
    <div class="card p-3 mt-3"><h2 class="h6">Tenant Exit Workflow</h2><form method="post" action="{{ $tenants->first() ? route('real-estate.tenants.offboarding',$tenants->first()) : '#' }}" class="row g-2">@csrf<div class="col-md-3"><select class="form-select" onchange="this.form.action='{{ url('/real-estate/tenants') }}/'+this.value+'/offboarding'">@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->client?->name }}</option>@endforeach</select></div><div class="col-md-3"><select class="form-select" name="step">@foreach(\Modules\RealEstate\Services\TenantOffboardingService::STEPS as $step)<option>{{ $step }}</option>@endforeach</select></div><div class="col-md-2"><input class="form-control" type="date" name="termination_date" placeholder="Termination"></div><div class="col-md-2"><input class="form-control" type="date" name="move_out_date" placeholder="Move out"></div><div class="col-md-2"><button class="btn btn-warning w-100" @disabled($tenants->isEmpty())>Update</button></div></form></div>
    <div class="card p-3 mt-3"><h2 class="h6">Tenant Billing Email Alerts</h2><form method="post" action="{{ $tenants->first() ? route('real-estate.tenants.billing-alerts.update',$tenants->first()) : '#' }}" class="row g-2" id="real-estate-tenant-alert-form">@csrf<div class="col-md-3"><select class="form-select" id="real-estate-tenant-alert-select">@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" data-email="{{ $tenant->client?->email }}" data-frequency="{{ $tenant->billing_alert_frequency ?? 'Monthly' }}" data-day="{{ $tenant->billing_alert_day ?? 1 }}" data-enabled="{{ $tenant->billing_alert_enabled ? '1' : '0' }}" data-subject="{{ $tenant->billing_alert_subject }}">{{ $tenant->client?->name }} {{ $tenant->client?->email ? '· '.$tenant->client->email : '' }}</option>@endforeach</select></div><div class="col-md-3"><input class="form-control" type="email" name="email" id="real-estate-tenant-alert-email" placeholder="Client email"></div><div class="col-md-2"><select class="form-select" name="billing_alert_frequency" id="real-estate-tenant-alert-frequency"><option>Monthly</option><option>Quarterly</option></select></div><div class="col-md-1"><input class="form-control" type="number" min="1" max="28" name="billing_alert_day" id="real-estate-tenant-alert-day" placeholder="Day" value="1" required></div><div class="col-md-2"><input class="form-control" name="billing_alert_subject" id="real-estate-tenant-alert-subject" placeholder="Email subject"></div><div class="col-md-1 d-flex align-items-center"><label class="mb-0"><input type="checkbox" name="billing_alert_enabled" id="real-estate-tenant-alert-enabled" value="1"> Send</label></div><div class="col-12"><button class="btn btn-warning" @disabled($tenants->isEmpty())>Save Alerts</button></div></form></div>
    <div class="card p-3 mt-3"><h2 class="h6">Tenant Archive Dashboard</h2><table class="table"><thead><tr><th>Tenant</th><th>Status</th><th>Move Out</th><th>Step</th><th>Lease History</th><th></th></tr></thead><tbody>@forelse($archiveTenants as $tenant)<tr><td>{{ $tenant->tenant_number }}<small class="d-block">{{ $tenant->client?->name }}</small></td><td>{{ $tenant->status }}</td><td>{{ $tenant->move_out_date?->toDateString() }}</td><td>{{ $tenant->offboarding_step }}</td><td>{{ $tenant->leases->count() }}</td><td><form method="post" action="{{ route('real-estate.tenants.restore',$tenant) }}">@csrf<button class="btn btn-sm btn-outline-warning">Restore</button></form></td></tr>@empty<tr><td colspan="6" class="text-muted">No archived, moved-out, or blacklisted tenants yet.</td></tr>@endforelse</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'leases' ? 'show active' : '' }}" id="re-leases">
    <div class="row g-3"><div class="col-lg-5"><div class="card p-3"><h2 class="h6">Lease Agreement</h2><form method="post" action="{{ route('real-estate.leases.store') }}" class="row g-2">@csrf
        <select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><select class="form-select" name="real_estate_unit_id"><option value="">Whole property</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->property?->property_name }} - {{ $unit->unit_number }}</option>@endforeach</select><select class="form-select" name="real_estate_tenant_id" required><option value="">Tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->client?->name }}</option>@endforeach</select>
        <div class="col-6"><input class="form-control" type="date" name="start_date" required></div><div class="col-6"><input class="form-control" type="date" name="end_date"></div><div class="col-6"><input class="form-control" type="number" step="0.01" name="rent_amount" placeholder="Rent" required></div><div class="col-6"><input class="form-control" type="number" step="0.01" name="deposit_amount" placeholder="Deposit"></div><select class="form-select" name="billing_cycle"><option>Monthly</option><option>Quarterly</option><option>Annual</option></select><input class="form-control" type="number" name="grace_period_days" placeholder="Grace days"><select class="form-select" name="status"><option>Draft</option><option>Active</option><option>Expired</option><option>Renewed</option><option>Terminated</option></select><label><input type="checkbox" name="auto_billing" value="1"> Auto billing</label><button class="btn btn-warning">Save Lease</button>
    </form></div></div><div class="col-lg-7"><div class="card p-3"><h2 class="h6">Leases & Rental Billing</h2><table class="table align-middle"><thead><tr><th>Lease</th><th>Tenant</th><th>Rent</th><th>Status</th><th></th></tr></thead><tbody>@foreach($leases as $lease)<tr><td>{{ $lease->lease_number }}<small class="d-block">{{ $lease->property?->property_name }} {{ $lease->unit?->unit_number }}</small></td><td>{{ $lease->tenant?->client?->name }}</td><td>{{ $money($lease->rent_amount) }}</td><td>{{ $lease->status }}</td><td><form method="post" action="{{ route('real-estate.leases.bill',$lease) }}">@csrf<button class="btn btn-sm btn-outline-warning">Bill Rent</button></form></td></tr>@endforeach</tbody></table></div></div></div>
    <div class="card p-3 mt-3"><h2 class="h6">Rental Charges</h2><table class="table"><thead><tr><th>Charge</th><th>Lease</th><th>Amount</th><th>Status</th><th>Invoice</th></tr></thead><tbody>@foreach($charges as $charge)<tr><td>{{ $charge->charge_number }}</td><td>{{ $charge->lease?->lease_number }}</td><td>{{ $money($charge->amount) }}</td><td>{{ $charge->status }}</td><td>@if($charge->invoice)<a href="{{ route('invoices.show',$charge->invoice) }}">{{ $charge->invoice->invoice_number }}</a>@endif</td></tr>@endforeach</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'payments' ? 'show active' : '' }}" id="re-payments">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h6">Record Client Payment</h2>
                <form method="post" action="{{ route('real-estate.payments.store') }}" class="row g-2" id="real-estate-payment-form">
                    @csrf
                    <select class="form-select" name="client_id" id="real-estate-payment-client">
                        <option value="">Client / Tenant</option>
                        @foreach($paymentClients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="tenant_id" id="real-estate-payment-tenant">
                        <option value="">Tenant</option>
                        @foreach($paymentTenants as $tenant)
                            <option value="{{ $tenant->id }}" data-client-id="{{ $tenant->client_id }}">{{ $tenant->tenant_number }} - {{ $tenant->client?->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="unit_id" id="real-estate-payment-unit">
                        <option value="">Unit</option>
                        @foreach($paymentUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->property?->property_name }} - {{ $unit->unit_number }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="invoice_id" id="real-estate-payment-invoice">
                        <option value="">Outstanding invoice</option>
                        @foreach($paymentInvoices as $invoice)
                            @php($invoiceContext = $paymentInvoiceContexts->get($invoice->id, []))
                            <option value="{{ $invoice->id }}" data-client-id="{{ $invoice->client_id }}" data-tenant-id="{{ $invoiceContext['tenant_id'] ?? '' }}" data-unit-id="{{ $invoiceContext['unit_id'] ?? '' }}">{{ $invoice->invoice_number }} - {{ $invoice->client?->name }} - {{ $money($invoice->balance) }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" type="number" step="0.01" min="0.01" name="amount" placeholder="Amount received" required>
                    <select class="form-select" name="payment_method_id">
                        <option value="">Payment method</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" type="date" name="payment_date" value="{{ now()->toDateString() }}" required>
                    <input class="form-control" name="reference" placeholder="Reference / transaction ID">
                    <textarea class="form-control" name="notes" placeholder="Notes"></textarea>
                    <button class="btn btn-warning" @disabled($paymentInvoices->isEmpty())>Record Payment</button>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card p-3">
                <h2 class="h6">Payment-Ready Charges</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="min-width: 760px;"><thead><tr><th>Charge</th><th>Tenant</th><th>Amount</th><th>Status</th><th>Invoice</th></tr></thead><tbody>@forelse($charges as $charge)<tr><td class="text-nowrap">{{ $charge->charge_number }}</td><td>{{ $charge->lease?->tenant?->client?->name ?? 'Tenant' }}</td><td class="text-nowrap">{{ $money($charge->amount) }}</td><td class="text-nowrap">{{ $charge->status }}</td><td class="text-nowrap">@if($charge->invoice)<a href="{{ route('invoices.show',$charge->invoice) }}">{{ $charge->invoice->invoice_number }}</a>@else<span class="text-muted">Pending billing</span>@endif</td></tr>@empty<tr><td colspan="5" class="text-muted">No real estate charges are ready for payment.</td></tr>@endforelse</tbody></table>
                </div>
            </div>
            <div class="card p-3 mt-3">
                <h2 class="h6">Client Payments Received</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="min-width: 820px;"><thead><tr><th>Date</th><th>Client</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Receipt</th></tr></thead><tbody>@forelse($realEstatePayments as $payment)<tr><td class="text-nowrap">{{ $payment->payment_date?->toDateString() }}</td><td>{{ $payment->invoice?->client?->name }}</td><td class="text-nowrap">{{ $payment->invoice?->invoice_number }}</td><td class="text-nowrap">{{ $payment->paymentMethod?->name ?? 'Unspecified' }}</td><td class="text-nowrap">{{ $money($payment->amount) }}</td><td class="text-nowrap">@if($payment->receipt)<a href="{{ route('receipts.show',$payment->receipt) }}">{{ $payment->receipt->receipt_number }}</a>@else<span class="text-muted">Pending</span>@endif</td></tr>@empty<tr><td colspan="6" class="text-muted">No client payments recorded yet.</td></tr>@endforelse</tbody></table>
                </div>
            </div>
            <div class="card p-3 mt-3 real-estate-document-list">
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
                    <h2 class="h6 mb-0">Real Estate Invoices</h2>
                    <input class="form-control real-estate-document-search" type="search" data-real-estate-search="invoices" placeholder="Search invoices by client, invoice, reference, status, property or unit" aria-label="Search real estate invoices">
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 real-estate-document-table" style="min-width: 1080px;">
                        <thead><tr><th>Invoice Client</th><th>Issue Date</th><th>Due Date</th><th>Status</th><th>Total</th><th class="text-end">Actions</th></tr></thead>
                        <tbody data-real-estate-list="invoices">
                        @forelse($realEstateInvoices as $invoice)
                            <?php
                                $invoiceStatus = strtolower((string) $invoice->payment_status);
                                $invoiceStatusColor = $invoiceStatus === 'paid' ? '#16a34a' : ($invoiceStatus === 'partial' ? '#f59e0b' : '#ef4444');
                                $invoicePropertyName = data_get($invoice->industry_context, 'property_name');
                                $invoiceUnitNumber = data_get($invoice->industry_context, 'unit_number');
                                $invoiceSearch = collect([
                                    $invoice->invoice_number,
                                    $invoice->industry_reference,
                                    $invoice->client?->name,
                                    $invoice->invoice_date?->format('M d, Y'),
                                    $invoice->due_date?->format('M d, Y'),
                                    $invoice->payment_status,
                                    $invoicePropertyName,
                                    $invoiceUnitNumber,
                                    data_get($invoice->recipient_profile, 'tenant_number'),
                                ])->filter()->implode(' ');
                            ?>
                            <tr data-real-estate-search-row="{{ \Illuminate\Support\Str::lower($invoiceSearch) }}">
                                <td>
                                    <div class="d-flex flex-wrap gap-2 align-items-baseline">
                                        <a class="text-nowrap fw-semibold" href="{{ route('invoices.show',$invoice) }}">{{ $invoice->invoice_number }}</a>
                                        <span>{{ $invoice->client?->name }}</span>
                                    </div>
                                    <small class="text-muted">@if($invoice->industry_reference){{ $invoice->industry_reference }}@endif @if($invoicePropertyName) · {{ $invoicePropertyName }}@endif @if($invoiceUnitNumber) · Unit {{ $invoiceUnitNumber }}@endif</small>
                                </td>
                                <td class="text-nowrap">{{ $invoice->invoice_date?->format('M d, Y') }}</td>
                                <td class="text-nowrap">{{ $invoice->due_date?->format('M d, Y') ?: '-' }}</td>
                                <td class="text-nowrap"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $invoiceStatusColor }};margin-right:8px;"></span>{{ \Illuminate\Support\Str::upper($invoice->payment_status) }}</td>
                                <td class="text-nowrap">{{ $money($invoice->total) }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a class="btn btn-sm btn-outline-dark rounded-pill px-3" href="{{ route('invoices.show',$invoice) }}">View</a>
                                        <form method="post" action="{{ route('invoices.destroy',$invoice) }}" onsubmit="const pin = prompt('Enter the 4-digit invoice delete PIN'); if (!pin) return false; this.querySelector('[name=delete_pin]').value = pin; return confirm('Delete this invoice?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="delete_pin">
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No real estate invoices yet.</td></tr>
                        @endforelse
                        <tr class="d-none" data-real-estate-empty="invoices"><td colspan="6" class="text-muted">No invoices match your search.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card p-3 mt-3 real-estate-document-list">
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
                    <h2 class="h6 mb-0">Real Estate Receipts</h2>
                    <input class="form-control real-estate-document-search" type="search" data-real-estate-search="receipts" placeholder="Search receipts by client, receipt, invoice, method or date" aria-label="Search real estate receipts">
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 real-estate-document-table" style="min-width: 1040px;">
                        <thead><tr><th>Receipt Client</th><th>Payment Date</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Balance</th><th class="text-end">Actions</th></tr></thead>
                        <tbody data-real-estate-list="receipts">
                        @forelse($realEstateReceipts as $receipt)
                            <?php
                                $receiptMethod = $receipt->payment?->paymentMethod?->name ?? $receipt->payment_method ?? 'Unspecified';
                                $receiptPropertyName = data_get($receipt->invoice?->industry_context, 'property_name');
                                $receiptUnitNumber = data_get($receipt->invoice?->industry_context, 'unit_number');
                                $receiptSearch = collect([
                                    $receipt->receipt_number,
                                    $receipt->invoice?->invoice_number,
                                    $receipt->invoice?->industry_reference,
                                    $receipt->invoice?->client?->name,
                                    $receiptMethod,
                                    $receipt->payment_date?->format('M d, Y'),
                                ])->filter()->implode(' ');
                            ?>
                            <tr data-real-estate-search-row="{{ \Illuminate\Support\Str::lower($receiptSearch) }}">
                                <td>
                                    <div class="d-flex flex-wrap gap-2 align-items-baseline">
                                        <a class="text-nowrap fw-semibold" href="{{ route('receipts.show',$receipt) }}">{{ $receipt->receipt_number }}</a>
                                        <span>{{ $receipt->invoice?->client?->name }}</span>
                                    </div>
                                    <small class="text-muted">@if($receipt->invoice?->industry_reference){{ $receipt->invoice->industry_reference }}@endif @if($receiptPropertyName) · {{ $receiptPropertyName }}@endif @if($receiptUnitNumber) · Unit {{ $receiptUnitNumber }}@endif</small>
                                </td>
                                <td class="text-nowrap">{{ $receipt->payment_date?->format('M d, Y') }}</td>
                                <td class="text-nowrap">{{ $receipt->invoice?->invoice_number }}</td>
                                <td class="text-nowrap">{{ $receiptMethod }}</td>
                                <td class="text-nowrap">{{ $money($receipt->amount_paid) }}</td>
                                <td class="text-nowrap">{{ $money($receipt->balance_remaining) }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a class="btn btn-sm btn-outline-dark rounded-pill px-3" href="{{ route('receipts.show',$receipt) }}">View</a>
                                        <a class="btn btn-sm btn-outline-warning rounded-pill px-3" href="{{ route('receipts.download',$receipt) }}">PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">No real estate receipts yet.</td></tr>
                        @endforelse
                        <tr class="d-none" data-real-estate-empty="receipts"><td colspan="7" class="text-muted">No receipts match your search.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tab-pane fade {{ $activeTab === 'utilities' ? 'show active' : '' }}" id="re-utilities">
    <div class="row g-3">
        <div class="col-lg-4"><div class="card p-3"><h2 class="h6">Utility Category</h2><form method="post" action="{{ route('real-estate.utility-types.store') }}" class="row g-2">@csrf<input class="form-control" name="name" placeholder="Utility name" required><select class="form-select" name="billing_method"><option>Metered</option><option>Flat</option><option>Subscription</option><option>Variable</option></select><input class="form-control" type="number" step="0.0001" name="default_rate" placeholder="Default rate"><textarea class="form-control" name="description" placeholder="Description"></textarea><label><input type="checkbox" name="is_custom" value="1"> Custom utility</label><button class="btn btn-warning">Save Utility</button></form></div></div>
        <div class="col-lg-4"><div class="card p-3"><h2 class="h6">Utility Meter</h2><form method="post" action="{{ route('real-estate.utility-meters.store') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><select class="form-select" name="real_estate_unit_id"><option value="">Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->property?->property_name }} - {{ $unit->unit_number }}</option>@endforeach</select><select class="form-select" name="real_estate_tenant_id"><option value="">Tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->client?->name }}</option>@endforeach</select><select class="form-select" name="real_estate_utility_type_id" required><option value="">Utility</option>@foreach($utilityTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select><input class="form-control" name="meter_number" placeholder="Meter number" required><select class="form-select" name="meter_type"><option>Water Meter</option><option>Electricity Meter</option><option>Gas Meter</option><option>Custom Meter</option></select><input class="form-control" type="number" step="0.0001" name="current_reading" placeholder="Current reading"><input class="form-control" type="number" step="0.0001" name="rate_per_unit" placeholder="Rate per unit"><button class="btn btn-warning">Save Meter</button></form></div></div>
        <div class="col-lg-4"><div class="card p-3"><h2 class="h6">Meter Reading</h2><form method="post" action="{{ route('real-estate.utility-readings.store') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_utility_meter_id" required><option value="">Meter</option>@foreach($utilityMeters as $meter)<option value="{{ $meter->id }}">{{ $meter->meter_number }} - {{ $meter->utilityType?->name }}</option>@endforeach</select><input class="form-control" type="number" step="0.0001" name="current_reading" placeholder="Current reading" required><input class="form-control" type="date" name="reading_date" value="{{ now()->toDateString() }}" required><input class="form-control" type="number" step="0.0001" name="rate_per_unit" placeholder="Override rate"><input type="hidden" name="generate_bill" value="1"><button class="btn btn-warning">Save & Bill</button></form></div></div>
    </div>
    <div class="card p-3 mt-3"><h2 class="h6">Utility Bills</h2><table class="table"><thead><tr><th>Bill</th><th>Tenant</th><th>Utility</th><th>Usage</th><th>Amount</th><th>Status</th><th>Invoice</th></tr></thead><tbody>@forelse($utilityBills as $bill)<tr><td>{{ $bill->bill_number }}</td><td>{{ $bill->tenant?->client?->name }}</td><td>{{ $bill->utilityType?->name }}</td><td>{{ $bill->quantity }}</td><td>{{ $money($bill->amount) }}</td><td>{{ $bill->status }}</td><td>@if($bill->invoice)<a href="{{ route('invoices.show',$bill->invoice) }}">{{ $bill->invoice->invoice_number }}</a>@endif</td></tr>@empty<tr><td colspan="7" class="text-muted">No utility bills yet.</td></tr>@endforelse</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'amenities' ? 'show active' : '' }}" id="re-amenities">
    <div class="row g-3"><div class="col-lg-5"><div class="card p-3"><h2 class="h6">Amenity</h2><form method="post" action="{{ route('real-estate.amenities.store') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_property_id"><option value="">Shared amenity</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><input class="form-control" name="name" placeholder="Amenity name" required><input class="form-control" type="number" name="capacity" placeholder="Capacity"><select class="form-select" name="fee_type"><option>Fixed</option><option>Free</option><option>Hourly</option><option>Daily</option></select><input class="form-control" type="number" step="0.01" name="fee_amount" placeholder="Fee amount"><textarea class="form-control" name="booking_rules" placeholder="Booking rules"></textarea><button class="btn btn-warning">Save Amenity</button></form></div></div><div class="col-lg-7"><div class="card p-3"><h2 class="h6">Tenant Booking</h2><form method="post" action="{{ route('real-estate.amenity-bookings.store') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_amenity_id" required><option value="">Amenity</option>@foreach($amenities as $amenity)<option value="{{ $amenity->id }}">{{ $amenity->name }}</option>@endforeach</select><select class="form-select" name="real_estate_tenant_id" required><option value="">Tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->client?->name }}</option>@endforeach</select><select class="form-select" name="real_estate_unit_id"><option value="">Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->unit_number }}</option>@endforeach</select><div class="col-4"><input class="form-control" type="date" name="booking_date" value="{{ now()->toDateString() }}" required></div><div class="col-4"><input class="form-control" type="time" name="start_time"></div><div class="col-4"><input class="form-control" type="time" name="end_time"></div><input class="form-control" type="number" step="0.01" name="charge_amount" placeholder="Charge override"><select class="form-select" name="status"><option>Pending</option><option>Confirmed</option><option>Completed</option><option>Cancelled</option></select><button class="btn btn-warning">Save Booking</button></form></div></div></div>
    <div class="card p-3 mt-3"><h2 class="h6">Amenity Bookings</h2><table class="table"><thead><tr><th>Booking</th><th>Amenity</th><th>Tenant</th><th>Date</th><th>Charge</th><th>Status</th></tr></thead><tbody>@forelse($amenityBookings as $booking)<tr><td>{{ $booking->booking_number }}</td><td>{{ $booking->amenity?->name }}</td><td>{{ $booking->tenant?->client?->name }}</td><td>{{ $booking->booking_date?->toDateString() }}</td><td>{{ $money($booking->charge_amount) }}</td><td>{{ $booking->status }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No amenity bookings yet.</td></tr>@endforelse</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'tenant-ledger' ? 'show active' : '' }}" id="re-tenant-ledger">
    <div class="card p-3 mb-3"><h2 class="h6">Generate Tenant Statement</h2><form method="post" action="{{ $tenants->first() ? route('real-estate.tenant-statements.store',$tenants->first()) : '#' }}" class="row g-2">@csrf<div class="col-md-4"><select class="form-select" name="tenant_selector" onchange="this.form.action='{{ url('/real-estate/tenants') }}/'+this.value+'/statements'">@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->client?->name }}</option>@endforeach</select></div><div class="col-md-3"><input class="form-control" type="date" name="period_start" value="{{ now()->startOfMonth()->toDateString() }}" required></div><div class="col-md-3"><input class="form-control" type="date" name="period_end" value="{{ now()->endOfMonth()->toDateString() }}" required></div><div class="col-md-2"><button class="btn btn-warning w-100" @disabled($tenants->isEmpty())>Generate</button></div></form></div>
    <div class="row g-3"><div class="col-lg-8"><div class="card p-3"><h2 class="h6">Tenant Account Ledger</h2><table class="table"><thead><tr><th>Date</th><th>Tenant</th><th>Type</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead><tbody>@forelse($tenantLedgerRows as $entry)<tr><td>{{ $entry->entry_date?->toDateString() }}</td><td>{{ $entry->tenant?->client?->name }}</td><td>{{ $entry->entry_type }}</td><td>{{ $entry->description }}</td><td>{{ $money($entry->debit) }}</td><td>{{ $money($entry->credit) }}</td><td>{{ $money($entry->running_balance) }}</td></tr>@empty<tr><td colspan="7" class="text-muted">No ledger entries yet.</td></tr>@endforelse</tbody></table></div></div><div class="col-lg-4"><div class="card p-3"><h2 class="h6">Statements</h2>@forelse($tenantStatements as $statement)<div class="border-top py-2"><strong>{{ $statement->statement_number }}</strong><span class="float-end">{{ $money($statement->outstanding_balance) }}</span><small class="d-block text-muted">{{ $statement->tenant?->client?->name }} · {{ $statement->period_start?->toDateString() }} to {{ $statement->period_end?->toDateString() }}</small></div>@empty<div class="text-muted">No statements generated.</div>@endforelse</div></div></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'collections' ? 'show active' : '' }}" id="re-collections">
    <div class="row g-3 mb-3">@foreach([['Outstanding Balances',$money($metrics['outstanding_balances'] ?? 0)],['Utility Revenue',$money($metrics['utility_revenue'] ?? 0)],['Amenity Revenue',$money($metrics['amenity_revenue'] ?? 0)],['Rental Revenue',$money($metrics['outstanding_rent'] ?? 0)]] as [$label,$value])<div class="col-md-3"><div class="card p-3"><span class="text-muted small">{{ $label }}</span><strong>{{ $value }}</strong></div></div>@endforeach</div>
    <div class="card p-3"><h2 class="h6">Outstanding Tenant Balances</h2><table class="table"><thead><tr><th>Tenant</th><th>Type</th><th>Description</th><th>Balance</th><th>Invoice</th></tr></thead><tbody>@forelse($tenantLedgerRows->where('running_balance','>',0) as $entry)<tr><td>{{ $entry->tenant?->client?->name }}</td><td>{{ $entry->entry_type }}</td><td>{{ $entry->description }}</td><td>{{ $money($entry->running_balance) }}</td><td>@if($entry->invoice)<a href="{{ route('invoices.show',$entry->invoice) }}">{{ $entry->invoice->invoice_number }}</a>@endif</td></tr>@empty<tr><td colspan="5" class="text-muted">No outstanding balances.</td></tr>@endforelse</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'consumption' ? 'show active' : '' }}" id="re-consumption">
    <div class="row g-3 mb-3">@foreach([['Water Consumption',$metrics['water_consumption'] ?? 0],['Electricity Consumption',$metrics['electricity_consumption'] ?? 0],['Utility Bills',$utilityBills->count()],['Active Meters',$utilityMeters->where('status','Active')->count()]] as [$label,$value])<div class="col-md-3"><div class="card p-3"><span class="text-muted small">{{ $label }}</span><strong>{{ $value }}</strong></div></div>@endforeach</div>
    <div class="card p-3"><h2 class="h6">Utility Consumption History</h2><table class="table"><thead><tr><th>Date</th><th>Tenant</th><th>Unit</th><th>Utility</th><th>Quantity</th><th>Charge</th></tr></thead><tbody>@forelse($utilityConsumption as $row)<tr><td>{{ $row->consumption_date?->toDateString() }}</td><td>{{ $row->tenant?->client?->name }}</td><td>{{ $row->unit?->unit_number }}</td><td>{{ $row->utilityType?->name }}</td><td>{{ $row->quantity }}</td><td>{{ $money($row->amount) }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No utility consumption yet.</td></tr>@endforelse</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'sales' ? 'show active' : '' }}" id="re-sales">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h6">Agent</h2>
                <form method="post" action="{{ route('real-estate.agents.store') }}" class="row g-2">
                    @csrf
                    <input class="form-control" name="name" placeholder="Agent name" required>
                    <input class="form-control" name="license_number" placeholder="License">
                    <input class="form-control" name="phone" placeholder="Phone">
                    <input class="form-control" name="email" placeholder="Email">
                    <select class="form-select" name="status"><option>Active</option><option>Suspended</option><option>Inactive</option></select>
                    <button class="btn btn-warning">Save Agent</button>
                </form>
                <div class="table-responsive border-top mt-3 pt-3">
                    <table class="table table-sm align-middle mb-0"><thead><tr><th>Agent</th><th>Status</th><th></th></tr></thead><tbody>
                    @forelse($agents as $agent)
                        <tr><td>{{ $agent->agent_number }}<small class="d-block text-muted">{{ $agent->name }}</small></td><td>{{ $agent->status }}</td><td class="text-end"><form method="post" action="{{ route('real-estate.agents.destroy',$agent) }}" onsubmit="return confirm('Delete this agent?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No agents yet.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h6">Listing</h2>
                <form method="post" action="{{ route('real-estate.listings.store') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select>
                    <select class="form-select" name="real_estate_agent_id"><option value="">Agent</option>@foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach</select>
                    <select class="form-select" name="listing_type"><option>Sale</option><option>Rent</option><option>Lease</option><option>Short Stay</option><option>Auction</option></select>
                    <input class="form-control" type="number" step="0.01" name="price" placeholder="Price" required>
                    <input class="form-control" type="date" name="listing_date" value="{{ now()->toDateString() }}" required>
                    <input class="form-control" type="date" name="expiry_date">
                    <select class="form-select" name="status"><option>Draft</option><option>Pending Approval</option><option>Approved</option><option>Published</option><option>Expired</option><option>Archived</option></select>
                    <input class="form-control" name="features" placeholder="Features, comma separated">
                    <label><input type="checkbox" name="is_featured" value="1"> Featured</label>
                    <button class="btn btn-warning">Save Listing</button>
                </form>
                <div class="table-responsive border-top mt-3 pt-3">
                    <table class="table table-sm align-middle mb-0"><thead><tr><th>Listing</th><th>Price</th><th></th></tr></thead><tbody>
                    @forelse($listings as $listing)
                        <tr><td>{{ $listing->listing_number }}<small class="d-block text-muted">{{ $listing->property?->property_name }} · {{ $listing->status }}</small></td><td>{{ $money($listing->price) }}</td><td class="text-end"><div class="d-flex gap-1 justify-content-end">@if($listing->status !== 'Approved')<form method="post" action="{{ route('real-estate.listings.approve',$listing) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>@endif<form method="post" action="{{ route('real-estate.listings.destroy',$listing) }}" onsubmit="return confirm('Delete this listing?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></div></td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No listings yet.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h6">Sale</h2>
                <form method="post" action="{{ route('real-estate.sales.store') }}" class="row g-2">
                    @csrf
                    <select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select>
                    <select class="form-select" name="real_estate_buyer_id" required><option value="">Buyer</option>@foreach($buyers as $buyer)<option value="{{ $buyer->id }}">{{ $buyer->client?->name }}</option>@endforeach</select>
                    <select class="form-select" name="real_estate_agent_id"><option value="">Agent</option>@foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach</select>
                    <input class="form-control" type="number" step="0.01" name="sale_price" placeholder="Sale price" required>
                    <input class="form-control" type="number" step="0.01" name="deposit" placeholder="Deposit">
                    <input class="form-control" type="date" name="completion_date">
                    <select class="form-select" name="status"><option>Reserved</option><option>Installment</option><option>Agreement</option><option>Completed</option><option>Cancelled</option></select>
                    <button class="btn btn-warning">Save Sale</button>
                </form>
                <div class="table-responsive border-top mt-3 pt-3">
                    <table class="table table-sm align-middle mb-0"><thead><tr><th>Sale</th><th>Balance</th><th></th></tr></thead><tbody>
                    @forelse($sales as $sale)
                        <tr><td>{{ $sale->sale_number }}<small class="d-block text-muted">{{ $sale->buyer?->client?->name }} · {{ $sale->property?->property_name }} · {{ $sale->status }}</small></td><td>{{ $money($sale->balance) }}</td><td class="text-end"><form method="post" action="{{ route('real-estate.sales.destroy',$sale) }}" onsubmit="return confirm('Delete this sale?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No sales yet.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div>
        </div>
    </div>
    <div class="card p-3 mt-3"><h2 class="h6">Commissions</h2><table class="table"><thead><tr><th>Agent</th><th>Type</th><th>Earned</th><th>Paid</th><th>Status</th></tr></thead><tbody>@forelse($commissions as $commission)<tr><td>{{ $commission->agent?->name }}</td><td>{{ $commission->commission_type }}</td><td>{{ $money($commission->earned_amount) }}</td><td>{{ $money($commission->paid_amount) }}</td><td>{{ $commission->status }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No commissions calculated yet.</td></tr>@endforelse</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'maintenance' ? 'show active' : '' }}" id="re-maintenance">
    <div class="row g-3">
        <div class="col-lg-4"><div class="card p-3 h-100"><h2 class="h6">Maintenance Work Order</h2><form method="post" action="{{ route('real-estate.maintenance.store') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><select class="form-select" name="real_estate_unit_id"><option value="">Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->unit_number }}</option>@endforeach</select><select class="form-select" name="real_estate_tenant_id"><option value="">Tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->client?->name }}</option>@endforeach</select><select class="form-select" name="technician_id"><option value="">Technician</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><select class="form-select" name="maintenance_type"><option>Corrective</option><option>Preventive</option><option>Emergency</option></select><select class="form-select" name="priority"><option>Medium</option><option>Low</option><option>High</option><option>Critical</option></select><input class="form-control" name="category" placeholder="Category"><textarea class="form-control" name="description" placeholder="Description" required></textarea><div class="col-6"><input class="form-control" type="number" step="0.01" name="estimated_cost" placeholder="Estimated cost"></div><div class="col-6"><input class="form-control" type="date" name="scheduled_date"></div><select class="form-select" name="status"><option>Open</option><option>Assigned</option><option>In Progress</option><option>Completed</option><option>Closed</option></select><button class="btn btn-warning">Save Work Order</button></form></div></div>
        <div class="col-lg-4"><div class="card p-3 h-100"><h2 class="h6">Tenant Service Request</h2><form method="post" action="{{ route('real-estate.service-requests.store') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_tenant_id"><option value="">Tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->client?->name }}</option>@endforeach</select><select class="form-select" name="real_estate_property_id"><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><select class="form-select" name="real_estate_unit_id"><option value="">Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->unit_number }}</option>@endforeach</select><select class="form-select" name="assigned_to"><option value="">Assign to</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><select class="form-select" name="request_type"><option>Plumbing Issues</option><option>Electrical Issues</option><option>Security Issues</option><option>Cleaning Requests</option><option>Repairs</option></select><textarea class="form-control" name="description" placeholder="Request details" required></textarea><select class="form-select" name="status"><option>Open</option><option>Assigned</option><option>In Progress</option><option>Resolved</option><option>Closed</option></select><button class="btn btn-warning">Save Service Request</button></form></div></div>
        <div class="col-lg-4"><div class="card p-3 h-100"><h2 class="h6">Maintenance Queue</h2><table class="table table-sm"><thead><tr><th>Request</th><th>Priority</th><th>Cost</th><th>Status</th></tr></thead><tbody>@forelse($maintenance as $request)<tr><td>{{ $request->request_number }}<small class="d-block text-muted">{{ $request->property?->property_name }}</small></td><td>{{ $request->priority }}</td><td>{{ $money($request->actual_cost) }}</td><td>{{ $request->status }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No work orders yet.</td></tr>@endforelse</tbody></table></div></div>
    </div>
    <div class="card p-3 mt-3"><h2 class="h6">Tenant Service Requests</h2><table class="table"><thead><tr><th>Request</th><th>Tenant</th><th>Property</th><th>Type</th><th>Assignee</th><th>Status</th><th>Resolution Time</th></tr></thead><tbody>@forelse($serviceRequests as $request)<tr><td>{{ $request->request_number }}</td><td>{{ $request->tenant?->client?->name }}</td><td>{{ $request->property?->property_name }}</td><td>{{ $request->request_type }}</td><td>{{ $request->assignee?->name }}</td><td>{{ $request->status }}</td><td>{{ $request->resolution_minutes ? round($request->resolution_minutes / 60, 1).'h' : 'Open' }}</td></tr>@empty<tr><td colspan="7" class="text-muted">No tenant service requests yet.</td></tr>@endforelse</tbody></table></div>
</div>

<div class="tab-pane fade {{ $activeTab === 'records' ? 'show active' : '' }}" id="re-records">
    <div class="row g-3"><div class="col-lg-4"><div class="card p-3 h-100"><h2 class="h6">Inspection</h2><form method="post" action="{{ route('real-estate.records.store','inspection') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><select class="form-select" name="real_estate_unit_id"><option value="">Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->unit_number }}</option>@endforeach</select><select class="form-select" name="inspector_id"><option value="">Inspector</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><select class="form-select" name="inspection_type"><option>Move In Inspection</option><option>Move Out Inspection</option><option>Routine Inspection</option><option>Maintenance Inspection</option><option>Valuation Inspection</option></select><input class="form-control" type="date" name="inspection_date" required><textarea class="form-control" name="findings" placeholder="Findings"></textarea><textarea class="form-control" name="recommendations" placeholder="Recommendations"></textarea><input class="form-control" name="photos" placeholder="Photo references, comma separated"><select class="form-select" name="status"><option>Draft</option><option>Completed</option><option>Approved</option></select><button class="btn btn-warning">Save Inspection</button></form></div></div><div class="col-lg-4"><div class="card p-3 h-100"><h2 class="h6">Valuation</h2><form method="post" action="{{ route('real-estate.records.store','valuation') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_property_id" required><option value="">Property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><select class="form-select" name="valuer_id"><option value="">Valuer</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><input class="form-control" type="date" name="valuation_date" required><input class="form-control" type="number" step="0.01" name="market_value" placeholder="Market value" required><input class="form-control" type="number" step="0.01" name="rental_value" placeholder="Rental value"><textarea class="form-control" name="notes" placeholder="Notes"></textarea><select class="form-select" name="status"><option>Draft</option><option>Approved</option><option>Archived</option></select><button class="btn btn-warning">Save Valuation</button></form></div></div><div class="col-lg-4"><div class="card p-3 h-100"><h2 class="h6">Land Parcel</h2><form method="post" action="{{ route('real-estate.records.store','land') }}" class="row g-2">@csrf<select class="form-select" name="real_estate_property_id"><option value="">Linked property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->property_name }}</option>@endforeach</select><input class="form-control" name="parcel_number" placeholder="Parcel number" required><input class="form-control" name="title_number" placeholder="Title number"><input class="form-control" type="number" step="0.0001" name="land_size" placeholder="Land size" required><select class="form-select" name="land_size_unit"><option>Acres</option><option>Hectares</option><option>Sq Ft</option><option>Sq M</option></select><input class="form-control" name="zoning" placeholder="Zoning"><select class="form-select" name="ownership_status"><option>Owned</option><option>Leased</option><option>Under Transfer</option><option>Disputed</option></select><input class="form-control" name="ownership_history" placeholder="Ownership history"><input class="form-control" name="sales_history" placeholder="Sales history"><button class="btn btn-warning">Save Land</button></form></div></div></div>
    <div class="row g-3 mt-1"><div class="col-lg-4"><div class="card p-3"><h2 class="h6">Inspection Reports</h2><table class="table"><thead><tr><th>No.</th><th>Property</th><th>Unit</th><th>Status</th></tr></thead><tbody>@forelse($inspections as $inspection)<tr><td>{{ $inspection->inspection_number }}</td><td>{{ $inspection->property?->property_name }}</td><td>{{ $inspection->unit?->unit_number }}</td><td>{{ $inspection->status }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No inspections yet.</td></tr>@endforelse</tbody></table></div></div><div class="col-lg-4"><div class="card p-3"><h2 class="h6">Valuation History</h2><table class="table"><thead><tr><th>Property</th><th>Market</th><th>Rental</th><th>Status</th></tr></thead><tbody>@forelse($valuations as $valuation)<tr><td>{{ $valuation->property?->property_name }}</td><td>{{ $money($valuation->market_value) }}</td><td>{{ $money($valuation->rental_value) }}</td><td>{{ $valuation->status }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No valuations yet.</td></tr>@endforelse</tbody></table></div></div><div class="col-lg-4"><div class="card p-3"><h2 class="h6">Land Register</h2><table class="table"><thead><tr><th>Parcel</th><th>Title</th><th>Property</th><th>Size</th></tr></thead><tbody>@forelse($landParcels as $parcel)<tr><td>{{ $parcel->parcel_number }}</td><td>{{ $parcel->title_number }}</td><td>{{ $parcel->property?->property_name }}</td><td>{{ $parcel->land_size }} {{ $parcel->land_size_unit }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No land parcels yet.</td></tr>@endforelse</tbody></table></div></div></div>
</div>
<div class="tab-pane fade {{ $activeTab === 'documents' ? 'show active' : '' }}" id="re-documents">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h6">Document Vault</h2>
                <form method="post" action="{{ route('real-estate.documents.store') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <select class="form-select" name="documentable_type">
                        <option value="">General document</option>
                        <option value="property">Property</option>
                        <option value="unit">Unit</option>
                        <option value="tenant">Tenant</option>
                        <option value="lease">Lease</option>
                        <option value="listing">Listing</option>
                        <option value="sale">Sale</option>
                        <option value="inspection">Inspection</option>
                        <option value="service-request">Service Request</option>
                        <option value="valuation">Valuation</option>
                        <option value="land">Land Parcel</option>
                        <option value="development">Development Project</option>
                    </select>
                    <input class="form-control" type="number" min="1" name="documentable_id" placeholder="Record ID">
                    <select class="form-select" name="document_template_id"><option value="">Template</option>@foreach($documentTemplates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select>
                    <input class="form-control" name="document_type" placeholder="Document type" required>
                    <input class="form-control" name="title" placeholder="Title" required>
                    <input class="form-control" type="file" name="file">
                    <select class="form-select" name="status"><option>Active</option><option>Draft</option><option>Expired</option><option>Archived</option></select>
                    <button class="btn btn-warning">Save Document</button>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card p-3 h-100 real-estate-document-list">
                <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                    <h2 class="h6 mb-0">Document Register</h2>
                    <input class="form-control form-control-sm real-estate-document-search" data-real-estate-search="documents" placeholder="Search documents">
                </div>
                <div class="table-responsive">
                    <table class="table real-estate-document-table">
                        <thead><tr><th>Title</th><th>Type</th><th>Linked Record</th><th>Status</th><th></th></tr></thead>
                        <tbody data-real-estate-list="documents">
                        @forelse($documents as $document)
                            @php($search = strtolower(trim($document->title.' '.$document->document_type.' '.$document->status.' '.class_basename($document->documentable_type ?? ''))))
                            <tr data-real-estate-search-row="{{ $search }}">
                                <td>{{ $document->title }}<small class="d-block text-muted">#{{ $document->id }} {{ $document->file_path ? basename($document->file_path) : 'Template or register entry' }}</small></td>
                                <td>{{ $document->document_type }}</td>
                                <td>{{ $document->documentable ? class_basename($document->documentable).' #'.$document->documentable_id : 'General' }}</td>
                                <td>{{ $document->status }}</td>
                                <td class="text-end"><div class="d-flex gap-1 justify-content-end">@if($document->file_path)<a class="btn btn-sm btn-outline-dark" href="{{ route('real-estate.documents.download',$document) }}">Download</a>@endif<form method="post" action="{{ route('real-estate.documents.destroy',$document) }}" onsubmit="return confirm('Delete this document?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No real estate documents yet.</td></tr>
                        @endforelse
                            <tr class="d-none" data-real-estate-empty="documents"><td colspan="5" class="text-muted">No documents match your search.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<style>
    .real-estate-document-list { overflow:hidden; }
    .real-estate-document-search { max-width:460px; border-radius:999px; }
    .real-estate-document-table thead th { color:#64748b; font-size:.78rem; letter-spacing:.18em; text-transform:uppercase; border-bottom:1px solid #dbe4ee; white-space:nowrap; }
    .real-estate-document-table tbody td { border-bottom:0; padding-top:.82rem; padding-bottom:.82rem; font-size:.98rem; }
    .real-estate-document-table tbody tr + tr td { border-top:1px solid rgba(148,163,184,.18); }
</style>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const clientSelect = document.getElementById('real-estate-payment-client');
    const tenantSelect = document.getElementById('real-estate-payment-tenant');
    const unitSelect = document.getElementById('real-estate-payment-unit');
    const invoiceSelect = document.getElementById('real-estate-payment-invoice');

    if (! clientSelect || ! invoiceSelect) {
        return;
    }

    const filterInvoices = () => {
        const clientId = clientSelect.value;
        const tenantId = tenantSelect?.value || '';
        const unitId = unitSelect?.value || '';

        if (tenantSelect && clientId) {
            Array.from(tenantSelect.options).forEach((option) => {
                const optionClientId = option.dataset.clientId || '';
                option.hidden = Boolean(option.value && optionClientId && optionClientId !== clientId);
            });

            const selectedTenant = tenantSelect.selectedOptions[0];
            if (selectedTenant && selectedTenant.hidden) {
                tenantSelect.value = '';
            }
        } else if (tenantSelect) {
            Array.from(tenantSelect.options).forEach((option) => option.hidden = false);
        }

        Array.from(invoiceSelect.options).forEach((option) => {
            const optionClientId = option.dataset.clientId || '';
            const optionTenantId = option.dataset.tenantId || '';
            const optionUnitId = option.dataset.unitId || '';
            option.hidden = Boolean(
                option.value
                && ((clientId && optionClientId !== clientId)
                    || (tenantId && optionTenantId !== tenantId)
                    || (unitId && optionUnitId !== unitId))
            );
        });

        const selected = invoiceSelect.selectedOptions[0];
        if (selected && selected.hidden) {
            invoiceSelect.value = '';
        }
    };

    tenantSelect?.addEventListener('change', () => {
        const selected = tenantSelect.selectedOptions[0];
        if (selected?.dataset.clientId && ! clientSelect.value) {
            clientSelect.value = selected.dataset.clientId;
        }
        filterInvoices();
    });
    clientSelect.addEventListener('change', filterInvoices);
    unitSelect?.addEventListener('change', filterInvoices);
    filterInvoices();

    document.querySelectorAll('[data-real-estate-search]').forEach((input) => {
        const listName = input.dataset.realEstateSearch;
        const list = document.querySelector(`[data-real-estate-list="${listName}"]`);
        const emptyRow = document.querySelector(`[data-real-estate-empty="${listName}"]`);
        const rows = list ? Array.from(list.querySelectorAll('[data-real-estate-search-row]')) : [];

        const applySearch = () => {
            const term = input.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const matches = ! term || row.dataset.realEstateSearchRow.includes(term);
                row.classList.toggle('d-none', ! matches);
                if (matches) {
                    visible += 1;
                }
            });

            emptyRow?.classList.toggle('d-none', visible !== 0);
        };

        input.addEventListener('input', applySearch);
        applySearch();
    });

    const alertForm = document.getElementById('real-estate-tenant-alert-form');
    const alertTenant = document.getElementById('real-estate-tenant-alert-select');
    const alertEmail = document.getElementById('real-estate-tenant-alert-email');
    const alertFrequency = document.getElementById('real-estate-tenant-alert-frequency');
    const alertDay = document.getElementById('real-estate-tenant-alert-day');
    const alertEnabled = document.getElementById('real-estate-tenant-alert-enabled');
    const alertSubject = document.getElementById('real-estate-tenant-alert-subject');

    if (alertForm && alertTenant) {
        const fillAlertFields = () => {
            const option = alertTenant.selectedOptions[0];
            if (! option) {
                return;
            }

            alertForm.action = `{{ url('/real-estate/tenants') }}/${option.value}/billing-alerts`;
            alertEmail.value = option.dataset.email || '';
            alertFrequency.value = option.dataset.frequency || 'Monthly';
            alertDay.value = option.dataset.day || 1;
            alertEnabled.checked = option.dataset.enabled === '1';
            alertSubject.value = option.dataset.subject || '';
        };

        alertTenant.addEventListener('change', fillAlertFields);
        fillAlertFields();
    }
});
</script>
@endpush
