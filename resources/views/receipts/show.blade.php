@extends('layouts.app')
@section('title',$receipt->receipt_number)
@section('content')
<div class="d-flex gap-2 justify-content-end mb-3"><a class="btn btn-outline-warning" href="{{ route('receipts.download',$receipt) }}"><i class="bi bi-download"></i> PDF</a><a class="btn btn-outline-primary" href="{{ route('receipts.email',$receipt) }}"><i class="bi bi-envelope"></i> Email</a>@if(\Illuminate\Support\Facades\Schema::hasTable('letters'))<a class="btn btn-outline-warning" href="{{ route('letters.from-receipt',$receipt) }}"><i class="bi bi-envelope-paper"></i> Acknowledgement</a>@endif</div>
<div class="card"><div class="card-body">
    <div class="row g-4"><div class="col-md-6"><h2 class="h5">Receipt</h2><p class="mb-1"><strong>{{ $receipt->receipt_number }}</strong></p><p class="mb-1">Invoice: <a href="{{ route('invoices.show',$receipt->invoice) }}">{{ $receipt->invoice->invoice_number }}</a></p><p class="mb-1">Payment date: {{ $receipt->payment_date?->format('d M Y') }}</p><p class="mb-1">Method: {{ $receipt->payment_method }}</p></div><div class="col-md-6"><h2 class="h5">Client</h2><p class="mb-1">{{ $receipt->invoice->client->name }}</p><p class="mb-1">{{ $receipt->invoice->client->email }}</p><p class="text-muted">{{ $receipt->invoice->client->address }}</p></div></div>
    <hr><div class="row justify-content-end"><div class="col-md-4"><table class="table"><tr><th>Amount paid</th><td class="text-end">{{ number_format($receipt->amount_paid,2) }}</td></tr><tr><th>Balance</th><td class="text-end">{{ number_format($receipt->balance_remaining,2) }}</td></tr></table></div></div>
    @if($receipt->emailLogs->count())<h3 class="h6 mt-4">Email History</h3>@foreach($receipt->emailLogs as $log)<div class="border-top py-2">{{ $log->recipient_email }} · {{ $log->subject }} <span class="float-end">{{ $log->status }}</span></div>@endforeach @endif
</div></div>
@if(\Illuminate\Support\Facades\Schema::hasTable('letters'))
<div class="card mt-3"><div class="card-body"><h2 class="h5">Letters</h2>@forelse($receipt->letters as $letter)<div class="border-top py-2"><a href="{{ route('letters.show',$letter) }}">{{ $letter->letter_number }}</a><span class="float-end">{{ $letter->status }}</span><div class="small text-muted">{{ $letter->subject }}</div></div>@empty<div class="text-muted">No letters linked to this receipt.</div>@endforelse</div></div>
@endif
@endsection
