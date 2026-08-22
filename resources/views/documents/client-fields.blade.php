@php
    $selectedMode = old('client_mode', 'existing');
    $selectedClient = old('client_id', $document->client_id);
@endphp
<div class="col-12">
    <label class="form-label">Client</label>
    <div class="d-flex gap-2 flex-wrap mb-2">
        <label class="btn btn-outline-dark btn-sm mb-0">
            <input class="form-check-input me-1 client-mode" type="radio" name="client_mode" value="existing" @checked($selectedMode === 'existing')>
            Select existing
        </label>
        <label class="btn btn-outline-warning btn-sm mb-0">
            <input class="form-check-input me-1 client-mode" type="radio" name="client_mode" value="new" @checked($selectedMode === 'new')>
            New client
        </label>
    </div>
    <div id="existing-client-box">
        <select class="form-select" name="client_id">
            <option value="">Select client</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected($selectedClient == $client->id)>{{ $client->name }}{{ $client->company_name ? ' - '.$client->company_name : '' }}</option>
            @endforeach
        </select>
    </div>
    <div id="new-client-box" class="border rounded p-3 mt-3 bg-light">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Client name</label><input class="form-control" name="client[name]" value="{{ old('client.name') }}"></div>
            <div class="col-md-6"><label class="form-label">Company name</label><input class="form-control" name="client[company_name]" value="{{ old('client.company_name') }}"></div>
            @if(\App\Models\Client::supportsCompanyStructure())<div class="col-md-4"><label class="form-label">Type</label><select class="form-select" name="client[type]"><option value="individual">Individual</option><option value="company" @selected(old('client.type') === 'company')>Company</option></select></div><div class="col-md-4"><label class="form-label">KRA PIN</label><input class="form-control" name="client[kra_pin]" value="{{ old('client.kra_pin') }}"></div>@endif
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="client[email]" value="{{ old('client.email') }}"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="client[phone]" value="{{ old('client.phone') }}"></div>
            <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="client[address]" rows="2">{{ old('client.address') }}</textarea></div>
            @if(\App\Models\Client::supportsCompanyStructure())<div class="col-12"><label class="form-label">Billing Address</label><textarea class="form-control" name="client[billing_address]" rows="2">{{ old('client.billing_address') }}</textarea></div>@endif
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="client[notes]" rows="2">{{ old('client.notes') }}</textarea></div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const existingBox = document.getElementById('existing-client-box');
    const newBox = document.getElementById('new-client-box');
    const updateClientMode = () => {
        const mode = document.querySelector('input[name="client_mode"]:checked')?.value || 'existing';
        existingBox.classList.toggle('d-none', mode !== 'existing');
        newBox.classList.toggle('d-none', mode !== 'new');
    };
    document.querySelectorAll('.client-mode').forEach((input) => input.addEventListener('change', updateClientMode));
    updateClientMode();
});
</script>
@endpush
