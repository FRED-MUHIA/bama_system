@extends('layouts.app')
@section('title',$client->name)
@section('content')
@php $companyStructure = \App\Models\Client::supportsCompanyStructure(); @endphp
<div class="d-flex gap-2 justify-content-end mb-3">
    <a class="btn btn-outline-dark" href="{{ route('clients.edit',$client) }}"><i class="bi bi-pencil"></i> Edit</a>
    <form method="post" action="{{ route('clients.destroy',$client) }}" onsubmit="return confirm('Delete this client?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
</div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h5">Client Details</h2>@if($companyStructure)<p class="mb-1"><span class="status-pill">{{ $client->type ?: 'individual' }}</span></p>@endif<p class="mb-1">{{ $client->company_name }}</p><p class="mb-1">{{ $client->email }}</p><p class="mb-1">{{ $client->phone }}</p><p class="mb-0 text-muted">{{ $client->address }}</p>@if($companyStructure)<hr><p class="mb-1"><strong>KRA PIN:</strong> {{ $client->kra_pin ?: '-' }}</p><p class="mb-0 text-muted">{{ $client->billing_address }}</p>@endif<hr><p>{{ $client->notes }}</p></div></div></div>
    <div class="col-lg-8"><div class="card"><div class="card-body"><h2 class="h5">History</h2>
        <h3 class="h6 mt-3">Invoices</h3>@foreach($client->invoices as $invoice)<div class="border-top py-2"><a href="{{ route('invoices.show',$invoice) }}">{{ $invoice->invoice_number }}</a> <span class="float-end">{{ number_format($invoice->total,2) }} · {{ $invoice->payment_status }}</span></div>@endforeach
        <h3 class="h6 mt-3">Quotations</h3>@foreach($client->quotations as $quotation)<div class="border-top py-2"><a href="{{ route('quotations.show',$quotation) }}">{{ $quotation->quotation_number }}</a> <span class="float-end">{{ number_format($quotation->total,2) }} · {{ $quotation->status }}</span></div>@endforeach
        <h3 class="h6 mt-3">Receipts</h3>@foreach($client->receipts as $receipt)<div class="border-top py-2"><a href="{{ route('receipts.show',$receipt) }}">{{ $receipt->receipt_number }}</a> <span class="float-end">{{ number_format($receipt->amount_paid,2) }}</span></div>@endforeach
        @if(\Illuminate\Support\Facades\Schema::hasTable('letters'))
            <h3 class="h6 mt-3">Letters</h3>@forelse($client->letters as $letter)<div class="border-top py-2"><a href="{{ route('letters.show',$letter) }}">{{ $letter->letter_number }}</a><span class="float-end">{{ $letter->type }} · {{ $letter->status }}</span><div class="small text-muted">{{ $letter->subject }}</div></div>@empty<div class="text-muted">No letters yet.</div>@endforelse
            <a class="btn btn-outline-warning btn-sm mt-2" href="{{ route('letters.create', ['client_id' => $client->id]) }}"><i class="bi bi-envelope-paper"></i> New Letter</a>
        @endif
    </div></div></div>
</div>
@if($companyStructure)
<div class="row g-4 mt-1">
    <div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h5">Contacts</h2>
        @forelse($client->contacts as $contact)<div class="border-top py-2"><strong>{{ $contact->full_name }}</strong>@if($contact->is_primary)<span class="badge bg-warning ms-1">Primary</span>@endif<br><span class="text-muted small">{{ $contact->position }} {{ $contact->email }} {{ $contact->phone }}</span></div>@empty<div class="text-muted">No contacts yet.</div>@endforelse
        <form class="border-top mt-3 pt-3" method="post" action="{{ route('clients.contacts.store',$client) }}">@csrf
            <input class="form-control mb-2" name="full_name" placeholder="Full name" required>
            <div class="row g-2"><div class="col-md-6"><input class="form-control" name="email" type="email" placeholder="Email"></div><div class="col-md-6"><input class="form-control" name="phone" placeholder="Phone"></div></div>
            <input class="form-control my-2" name="position" placeholder="Position">
            <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_primary" value="1"> Primary contact</label>
            <button class="btn btn-outline-warning btn-sm">Add Contact</button>
        </form>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h5">Sites</h2>
        @forelse($client->sites as $site)<div class="border-top py-2"><strong>{{ $site->site_name }}</strong><br><span class="text-muted small">{{ $site->address }}</span>@if(\Illuminate\Support\Facades\Schema::hasTable('letters'))<div class="mt-1">@forelse($site->letters as $letter)<a class="btn btn-sm btn-outline-dark mb-1" href="{{ route('letters.show',$letter) }}">{{ $letter->letter_number }}</a>@empty<span class="small text-muted">No site letters.</span>@endforelse</div>@endif</div>@empty<div class="text-muted">No sites yet.</div>@endforelse
        <form class="border-top mt-3 pt-3" method="post" action="{{ route('clients.sites.store',$client) }}">@csrf
            <input class="form-control mb-2" name="site_name" placeholder="Site name" required>
            <textarea class="form-control mb-2" name="address" rows="2" placeholder="Address"></textarea>
            <textarea class="form-control mb-2" name="notes" rows="2" placeholder="Notes"></textarea>
            <button class="btn btn-outline-warning btn-sm">Add Site</button>
        </form>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h5">Projects</h2>
        @forelse($client->projects as $project)<div class="border-top py-2"><a href="{{ route('projects.show',$project) }}">{{ $project->project_name }}</a><span class="float-end status-pill">{{ $project->status }}</span><br><span class="text-muted small">{{ $project->site?->site_name }}</span></div>@empty<div class="text-muted">No projects yet.</div>@endforelse
        <form class="border-top mt-3 pt-3" method="post" action="{{ route('clients.projects.store',$client) }}">@csrf
            <input class="form-control mb-2" name="project_name" placeholder="Project name" required>
            <select class="form-select mb-2" name="status">@foreach(\App\Models\Project::STATUSES as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select>
            <select class="form-select mb-2" name="site_id"><option value="">Site</option>@foreach($client->sites as $site)<option value="{{ $site->id }}">{{ $site->site_name }}</option>@endforeach</select>
            <select class="form-select mb-2" name="contact_id"><option value="">Contact</option>@foreach($client->contacts as $contact)<option value="{{ $contact->id }}">{{ $contact->full_name }}</option>@endforeach</select>
            <textarea class="form-control mb-2" name="scope" rows="2" placeholder="Scope"></textarea>
            <button class="btn btn-outline-warning btn-sm">Add Project</button>
        </form>
    </div></div></div>
</div>
<div class="card mt-4"><div class="card-body"><h2 class="h5">Merge Duplicate Client</h2>
    <form class="row g-2 align-items-end" method="post" action="{{ route('clients.merge',$client) }}">@csrf
        <div class="col-md-9"><label class="form-label">Move this client's records into</label><select class="form-select" name="target_client_id" required><option value="">Select target client</option>@foreach($clients as $target)<option value="{{ $target->id }}">{{ $target->name }}{{ $target->company_name ? ' - '.$target->company_name : '' }}</option>@endforeach</select></div>
        <div class="col-md-3"><button class="btn btn-outline-danger w-100" onclick="return confirm('Merge this client into the selected target?')">Merge</button></div>
    </form>
</div></div>
@endif
@endsection
