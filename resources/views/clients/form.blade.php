@extends('layouts.app')
@section('title', $client->exists ? 'Edit Client' : 'Add Client')
@section('content')
@php
    $companyStructure = \App\Models\Client::supportsCompanyStructure();
    $primaryContact = $companyStructure ? $client->primaryContact : null;
    $selectedType = old('type', $client->type ?: 'individual');
@endphp
<div class="card"><div class="card-body">
<form method="post" action="{{ $client->exists ? route('clients.update',$client) : route('clients.store') }}">@csrf @if($client->exists) @method('PUT') @endif
    <div class="row g-3">
        @if($companyStructure)<div class="col-md-4"><label class="form-label">Type</label><select class="form-select" name="type" id="client-type"><option value="individual" @selected($selectedType==='individual')>Individual</option><option value="company" @selected($selectedType==='company')>Company</option></select></div>@endif
        <div class="col-md-6"><label class="form-label">Client name</label><input class="form-control" name="name" value="{{ old('name',$client->name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Company name</label><input class="form-control" name="company_name" value="{{ old('company_name',$client->company_name) }}"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email',$client->email) }}"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone',$client->phone) }}"></div>
        <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address">{{ old('address',$client->address) }}</textarea></div>
        @if($companyStructure)<div class="col-md-8"><label class="form-label">Billing Address</label><textarea class="form-control" name="billing_address">{{ old('billing_address',$client->billing_address) }}</textarea></div><div class="col-md-4"><label class="form-label">KRA PIN</label><input class="form-control" name="kra_pin" value="{{ old('kra_pin',$client->kra_pin) }}"></div>@endif
        @if($companyStructure)
            <div class="col-12" id="company-employee-section" @style(['display:none' => $selectedType !== 'company'])>
                <div class="border rounded p-3">
                    <h2 class="h6 mb-3">Company Employee / Primary Contact</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Employee name</label><input class="form-control" name="primary_contact[full_name]" value="{{ old('primary_contact.full_name', $primaryContact?->full_name) }}"></div>
                        <div class="col-md-6"><label class="form-label">Title / Position</label><input class="form-control" name="primary_contact[position]" value="{{ old('primary_contact.position', $primaryContact?->position) }}"></div>
                        <div class="col-md-6"><label class="form-label">Employee email</label><input class="form-control" type="email" name="primary_contact[email]" value="{{ old('primary_contact.email', $primaryContact?->email) }}"></div>
                        <div class="col-md-6"><label class="form-label">Employee phone</label><input class="form-control" name="primary_contact[phone]" value="{{ old('primary_contact.phone', $primaryContact?->phone) }}"></div>
                    </div>
                </div>
            </div>
        @endif
        <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes">{{ old('notes',$client->notes) }}</textarea></div>
    </div>
    <div class="mt-4"><button class="btn btn-warning">Save Client</button> <a class="btn btn-link" href="{{ route('clients.index') }}">Cancel</a></div>
</form>
</div></div>
@endsection

@if($companyStructure)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('client-type');
    const employeeSection = document.getElementById('company-employee-section');
    if (!typeSelect || !employeeSection) return;

    const toggleEmployeeSection = () => {
        employeeSection.style.display = typeSelect.value === 'company' ? '' : 'none';
    };

    typeSelect.addEventListener('change', toggleEmployeeSection);
    toggleEmployeeSection();
});
</script>
@endpush
@endif
