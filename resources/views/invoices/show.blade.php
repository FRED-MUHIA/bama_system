@extends('layouts.app')
@section('title',$invoice->invoice_number)
@section('content')
@php $publicLink = route('public.invoices.show', $invoice->public_token); @endphp
<div class="d-flex gap-2 flex-wrap justify-content-end mb-3">
    @unless($invoice->isPartPayment())<a class="btn btn-outline-dark" href="{{ route('invoices.edit',$invoice) }}"><i class="bi bi-pencil"></i> Edit</a>@endunless
    <a class="btn btn-outline-warning" href="{{ route('invoices.download',$invoice) }}"><i class="bi bi-download"></i> PDF</a>
    <a class="btn btn-outline-primary" href="{{ route('invoices.email',$invoice) }}"><i class="bi bi-envelope"></i> Email</a>
    @if(\Illuminate\Support\Facades\Schema::hasTable('letters'))<a class="btn btn-outline-warning" href="{{ route('letters.from-invoice',$invoice) }}"><i class="bi bi-envelope-paper"></i> Balance Letter</a>@endif
    <a class="btn btn-warning" href="{{ $publicLink }}" target="_blank"><i class="bi bi-link-45deg"></i> Open Link</a>
    <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteInvoiceModal"><i class="bi bi-trash"></i> Delete</button>
</div>
<div class="card mb-3"><div class="card-body">
    <label class="form-label">Shareable invoice link</label>
    <div class="input-group">
        <input class="form-control" id="invoice-share-link" value="{{ $publicLink }}" readonly>
        <button class="btn btn-outline-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('invoice-share-link').value)">Copy</button>
    </div>
    <div class="d-flex align-items-center gap-3 mt-3">
        <img src="{{ $qrCode }}" alt="Invoice verification QR code" style="width:110px;height:110px;border:1px solid #e5e7eb;padding:6px;background:#fff">
        <div>
            <div class="fw-semibold">Scan to verify invoice</div>
            <div class="text-muted small">{{ $verificationUrl }}</div>
        </div>
    </div>
</div></div>
@include('documents.summary', ['type'=>'Invoice','document'=>$invoice,'number'=>$invoice->invoice_number,'date'=>$invoice->invoice_date,'status'=>$invoice->payment_status])
@if(\Illuminate\Support\Facades\Schema::hasTable('letters'))
<div class="card mt-3"><div class="card-body"><h2 class="h5">Letters</h2>@forelse($invoice->letters as $letter)<div class="border-top py-2"><a href="{{ route('letters.show',$letter) }}">{{ $letter->letter_number }}</a><span class="float-end">{{ $letter->type }} · {{ $letter->status }}</span><div class="small text-muted">{{ $letter->subject }}</div></div>@empty<div class="text-muted">No letters linked to this invoice.</div>@endforelse</div></div>
@endif
@if($invoice->isPartPayment())
    <div class="card mt-3"><div class="card-body">
        <h2 class="h5">Part Payment Allocation</h2>
        <p class="mb-1">Parent invoice: <a href="{{ route('invoices.show',$invoice->parentInvoice) }}">{{ $invoice->parentInvoice->invoice_number }}</a></p>
        <p class="mb-0">Allocated amount: <strong>{{ number_format($invoice->part_payment_amount,2) }}</strong></p>
    </div></div>
@elseif($remainingPartPaymentBalance !== null)
    <div class="row g-4 mt-1">
        <div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h5">Create Part Payment Invoice</h2><form method="post" action="{{ route('invoices.part-payments.store',$invoice) }}">@csrf
            <div class="mb-2 text-muted">Remaining allocation balance: {{ number_format($remainingPartPaymentBalance,2) }}</div>
            <div class="row g-2"><div class="col-md-6"><label class="form-label">Amount</label><input class="form-control" name="amount" type="number" step="0.01" min="0.01" max="{{ $remainingPartPaymentBalance }}" required></div><div class="col-md-6"><label class="form-label">Invoice date</label><input class="form-control" name="invoice_date" type="date" value="{{ now()->format('Y-m-d') }}" required></div></div>
            <label class="form-label mt-2">Due date</label><input class="form-control" name="due_date" type="date" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
            <label class="form-label mt-2">Notes</label><textarea class="form-control" name="notes"></textarea>
            <button class="btn btn-warning mt-3" @disabled($remainingPartPaymentBalance <= 0)>Create Part Payment Invoice</button>
        </form></div></div></div>
        <div class="col-lg-7"><div class="card"><div class="card-body"><h2 class="h5">Part Payment Invoices</h2>@forelse($invoice->partPaymentInvoices as $part)<div class="border-top py-2"><a href="{{ route('invoices.show',$part) }}">{{ $part->invoice_number }}</a><span class="float-end">{{ number_format($part->part_payment_amount,2) }}</span></div>@empty<div class="text-muted">No part payment invoices yet.</div>@endforelse</div></div></div>
    </div>
@endif
@if(\App\Models\Invoice::supportsInvoiceTypes() && \App\Models\Invoice::supportsAllocations() && ! $invoice->isAllocationInvoice())
<div class="card mt-4"><div class="card-body"><h2 class="h5">Advanced Allocation Invoice</h2>
    <form method="post" action="{{ route('invoices.advanced.store') }}">@csrf
        <input type="hidden" name="source_invoice_ids[]" value="{{ $invoice->id }}">
        <div class="row g-2">
            <div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="invoice_type"><option>STAGE_PAYMENT</option><option>PART_PAYMENT</option><option>VAT_ONLY</option><option>BALANCE</option><option>COMBINED</option></select></div>
            <div class="col-md-3"><label class="form-label">Mode</label><select class="form-select" name="allocation_mode"><option value="percentage">Percentage</option><option value="fixed">Fixed amount</option><option value="remaining">Remaining balance</option><option value="tax_only">VAT only</option></select></div>
            <div class="col-md-2"><label class="form-label">Percentage</label><input class="form-control" name="percentage" type="number" step="0.01" placeholder="80"></div>
            <div class="col-md-2"><label class="form-label">Amount</label><input class="form-control" name="amount" type="number" step="0.01"></div>
            <div class="col-md-2"><label class="form-label">Date</label><input class="form-control" name="invoice_date" type="date" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="col-md-4"><label class="form-label">Combine with</label><select class="form-select" name="source_invoice_ids[]" multiple>@foreach($sourceInvoices as $source)<option value="{{ $source->id }}">{{ $source->invoice_number }} - {{ number_format($source->total,2) }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
        <button class="btn btn-outline-warning mt-3">Generate Allocation Invoice</button>
    </form>
</div></div>
@endif
@unless($invoice->isPartPayment())
<div class="row g-4 mt-1">
    <div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h5">Record Payment</h2><form method="post" action="{{ route('invoices.payments.store',$invoice) }}">@csrf
        <div class="row g-2"><div class="col-md-6"><label class="form-label">Amount</label><input class="form-control" name="amount" type="number" step="0.01" max="{{ $invoice->balance }}" required></div><div class="col-md-6"><label class="form-label">Date</label><input class="form-control" name="payment_date" type="date" value="{{ now()->format('Y-m-d') }}" required></div></div>
        <label class="form-label mt-2">Payment method</label><select class="form-select" name="payment_method_id"><option value="">Select</option>@foreach($methods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select>
        <label class="form-label mt-2">Reference</label><input class="form-control" name="reference"><label class="form-label mt-2">Notes</label><textarea class="form-control" name="notes"></textarea>
        <button class="btn btn-warning mt-3">Generate Receipt</button>
    </form></div></div></div>
    <div class="col-lg-7"><div class="card"><div class="card-body"><h2 class="h5">Payments & Receipts</h2>@foreach($invoice->receipts as $receipt)<div class="border-top py-2"><a href="{{ route('receipts.show',$receipt) }}">{{ $receipt->receipt_number }}</a><span class="float-end">{{ number_format($receipt->amount_paid,2) }}</span></div>@endforeach</div></div></div>
</div>
@endunless
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-labelledby="deleteInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('invoices.destroy',$invoice) }}" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h2 class="modal-title h5" id="deleteInvoiceModalLabel">Delete invoice</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Enter the 4-digit PIN to permanently delete {{ $invoice->invoice_number }}.</p>
                <label class="form-label">Delete PIN</label>
                <input class="form-control" name="delete_pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete Invoice</button>
            </div>
        </form>
    </div>
</div>
@endsection
