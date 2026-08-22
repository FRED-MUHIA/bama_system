@extends('layouts.app')
@section('title', $invoice->exists ? 'Edit Invoice' : 'Create Invoice')
@section('content')
@php $items = old('items', $invoice->items?->toArray() ?: [['title'=>'','description'=>'','quantity'=>1,'unit_price'=>0,'discount'=>0,'tax_rate'=>'']]); @endphp
<form method="post" action="{{ $invoice->exists ? route('invoices.update',$invoice) : route('invoices.store') }}">@csrf @if($invoice->exists) @method('PUT') @endif
<div class="card mb-3"><div class="card-body"><div class="row g-3">
    @include('documents.client-fields', ['document' => $invoice])
    @include('documents.project-fields', ['document' => $invoice])
    @include('accounting.partials.tag-fields', ['document' => $invoice])
    <div class="col-md-4"><label class="form-label">Invoice date</label><input class="form-control" type="date" name="invoice_date" value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"></div>
    <div class="col-md-4"><label class="form-label">Due date</label><input class="form-control" type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}"></div>
    @if($printingInvoiceEnabled ?? false)
        @php
            $selectedPrintingJob = old('printing_job_id', data_get($invoice->industry_context, 'production_job_id'));
        @endphp
        <div class="col-12">
            <div class="border rounded p-3" style="background:#fbfffc">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div>
                        <label class="form-label mb-1">Printing production job</label>
                        <div class="small text-muted">Link this invoice to a production job, ticket, costing, and dispatch workflow.</div>
                    </div>
                    <a class="btn btn-sm btn-outline-dark" href="{{ route('printing-branding.jobs') }}">Open Jobs</a>
                </div>
                <div class="row g-2">
                    <div class="col-md-8">
                        <select class="form-select" name="printing_job_id" id="printing-job-select">
                            <option value="">Manual invoice / no production job</option>
                            @foreach($printingJobs as $job)
                                @php
                                    $total = (float) ($job->cost?->selling_price ?: $job->quotation?->total ?: 0);
                                    $unit = (float) $job->quantity > 0 ? round($total / (float) $job->quantity, 2) : $total;
                                    $specs = collect($job->specifications ?? [])->map(fn($value, $key) => $key.': '.$value)->implode(', ');
                                    $description = trim($job->job_number.' - '.$job->product_name.($specs ? ' | '.$specs : ''));
                                @endphp
                                <option value="{{ $job->id }}"
                                    data-client="{{ $job->client_id }}"
                                    data-title="{{ e($job->product_name) }}"
                                    data-description="{{ e($description) }}"
                                    data-quantity="{{ (float) $job->quantity }}"
                                    data-unit-price="{{ $unit }}"
                                    data-due="{{ $job->delivery_date?->toDateString() }}"
                                    @selected((string) $selectedPrintingJob === (string) $job->id)>
                                    {{ $job->job_number }} - {{ $job->client?->name }} - {{ $job->product_name }} - {{ $job->status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="printing_invoice_type">
                            @foreach(['Final Invoice','Proforma Invoice','Deposit Invoice','Stage Invoice','Balance Invoice'] as $type)
                                <option value="{{ $type }}" @selected(old('printing_invoice_type', data_get($invoice->industry_context, 'invoice_type', 'Final Invoice')) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div></div></div>
@include('documents.items-table', ['items' => $items])
<div class="card mt-3"><div class="card-body"><label class="form-label">Terms</label><textarea class="form-control mb-3" name="terms">{{ old('terms',$invoice->terms ?? $settings?->default_terms) }}</textarea><label class="form-label">Notes</label><textarea class="form-control" name="notes">{{ old('notes',$invoice->notes) }}</textarea></div></div>
<div class="mt-3"><button class="btn btn-warning">Save Invoice</button></div>
</form>
@if($printingInvoiceEnabled ?? false)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('printing-job-select');
    if (! select) return;

    const fillFromJob = () => {
        const option = select.selectedOptions[0];
        if (! option || ! option.value) return;

        const clientMode = document.querySelector('input[name="client_mode"][value="existing"]');
        const clientSelect = document.querySelector('select[name="client_id"]');
        if (clientMode) {
            clientMode.checked = true;
            clientMode.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (clientSelect && option.dataset.client) {
            clientSelect.value = option.dataset.client;
        }

        const row = document.querySelector('#items-table tbody tr');
        if (! row) return;
        row.querySelector('[name$="[title]"]').value = option.dataset.title || '';
        row.querySelector('[name$="[description]"]').value = option.dataset.description || '';
        row.querySelector('[name$="[quantity]"]').value = option.dataset.quantity || '1';
        row.querySelector('[name$="[unit_price]"]').value = option.dataset.unitPrice || '0';

        const dueInput = document.querySelector('input[name="due_date"]');
        if (dueInput && option.dataset.due) {
            dueInput.value = option.dataset.due;
        }
    };

    select.addEventListener('change', fillFromJob);
    if (select.value && ! @json($invoice->exists)) {
        fillFromJob();
    }
});
</script>
@endpush
@endif
@endsection
