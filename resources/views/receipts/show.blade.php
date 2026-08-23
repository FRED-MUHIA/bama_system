@extends('layouts.app')
@section('title',$receipt->receipt_number)
@section('content')
<div class="d-flex gap-2 justify-content-end mb-3"><a class="btn btn-outline-warning" href="{{ route('receipts.download',$receipt) }}"><i class="bi bi-download"></i> PDF</a><a class="btn btn-outline-primary" href="{{ route('receipts.email',$receipt) }}"><i class="bi bi-envelope"></i> Email</a>@if(\Illuminate\Support\Facades\Schema::hasTable('letters'))<a class="btn btn-outline-warning" href="{{ route('letters.from-receipt',$receipt) }}"><i class="bi bi-envelope-paper"></i> Acknowledgement</a>@endif</div>
@include('documents.document-sheet', ['type' => 'Receipt', 'document' => $receipt])
@if($receipt->emailLogs->count())<div class="card mt-3"><div class="card-body"><h3 class="h6">Email History</h3>@foreach($receipt->emailLogs as $log)<div class="border-top py-2">{{ $log->recipient_email }} · {{ $log->subject }} <span class="float-end">{{ $log->status }}</span></div>@endforeach</div></div>@endif
@if(\Illuminate\Support\Facades\Schema::hasTable('letters'))
<div class="card mt-3"><div class="card-body"><h2 class="h5">Letters</h2>@forelse($receipt->letters as $letter)<div class="border-top py-2"><a href="{{ route('letters.show',$letter) }}">{{ $letter->letter_number }}</a><span class="float-end">{{ $letter->status }}</span><div class="small text-muted">{{ $letter->subject }}</div></div>@empty<div class="text-muted">No letters linked to this receipt.</div>@endforelse</div></div>
@endif
@endsection
