@extends('layouts.app')
@section('title', $client->exists ? 'Edit Client' : 'Add Client')
@section('content')
@php
    $companyStructure = \App\Models\Client::supportsCompanyStructure();
    $primaryContact = $companyStructure ? $client->primaryContact : null;
    $selectedType = old('type', $client->type ?: 'individual');
@endphp
<div class="page-shell">
    <x-page-header :title="$client->exists ? 'Edit Client' : 'Add Client'" subtitle="Keep customer contact, billing, and primary contact details accurate.">
        <x-slot:actions>
            <a class="btn btn-outline-dark" href="{{ route('clients.index') }}"><i class="bi bi-arrow-left"></i> Back</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card"><div class="card-body">
<form method="post" action="{{ $client->exists ? route('clients.update',$client) : route('clients.store') }}">@csrf @if($client->exists) @method('PUT') @endif
    <div class="d-grid gap-3">
        <section class="form-section">
            <h2 class="form-section-title">Basic Information</h2>
            <div class="form-grid">
                @if($companyStructure)<div><label class="form-label">Type</label><select class="form-select" name="type" id="client-type"><option value="individual" @selected($selectedType==='individual')>Individual</option><option value="company" @selected($selectedType==='company')>Company</option></select></div>@endif
                <div><label class="form-label">Client name</label><input class="form-control" name="name" value="{{ old('name',$client->name) }}" required></div>
                <div><label class="form-label">Company name</label><input class="form-control" name="company_name" value="{{ old('company_name',$client->company_name) }}"></div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Contact Details</h2>
            <div class="form-grid">
                <div><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email',$client->email) }}"></div>
                <div><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone',$client->phone) }}"></div>
                <div style="grid-column:1/-1"><label class="form-label">Address</label><textarea class="form-control" name="address">{{ old('address',$client->address) }}</textarea></div>
            </div>
        </section>

        @if($companyStructure)
            <section class="form-section">
                <h2 class="form-section-title">Billing Details</h2>
                <div class="form-grid">
                    <div><label class="form-label">KRA PIN</label><input class="form-control" name="kra_pin" value="{{ old('kra_pin',$client->kra_pin) }}"></div>
                    <div style="grid-column:1/-1"><label class="form-label">Billing Address</label><textarea class="form-control" name="billing_address">{{ old('billing_address',$client->billing_address) }}</textarea></div>
                </div>
            </section>

            <section class="form-section" id="company-employee-section" @style(['display:none' => $selectedType !== 'company'])>
                <h2 class="form-section-title">Company Employee / Primary Contact</h2>
                <div class="form-grid">
                    <div><label class="form-label">Employee name</label><input class="form-control" name="primary_contact[full_name]" value="{{ old('primary_contact.full_name', $primaryContact?->full_name) }}"></div>
                    <div><label class="form-label">Title / Position</label><input class="form-control" name="primary_contact[position]" value="{{ old('primary_contact.position', $primaryContact?->position) }}"></div>
                    <div><label class="form-label">Employee email</label><input class="form-control" type="email" name="primary_contact[email]" value="{{ old('primary_contact.email', $primaryContact?->email) }}"></div>
                    <div><label class="form-label">Employee phone</label><input class="form-control" name="primary_contact[phone]" value="{{ old('primary_contact.phone', $primaryContact?->phone) }}"></div>
                </div>
            </section>
        @endif

        <section class="form-section">
            <h2 class="form-section-title">Notes</h2>
            <textarea class="form-control" name="notes">{{ old('notes',$client->notes) }}</textarea>
        </section>
    </div>
    <div class="sticky-mobile-actions mt-4"><button class="btn btn-warning">Save Client</button> <a class="btn btn-outline-dark" href="{{ route('clients.index') }}">Cancel</a></div>
</form>
</div></div>
</div>
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
