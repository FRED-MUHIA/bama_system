@extends('layouts.app')
@section('title','Send '.ucfirst($type))
@section('content')
@php
    $number = $type === 'quotation' ? $document->quotation_number : ($type === 'invoice' ? $document->invoice_number : $document->receipt_number);
    $client = $type === 'receipt' ? $document->invoice->client : $document->client;
@endphp
<div class="card"><div class="card-body">
    <form method="post" action="{{ route($type.'s.email.send', $document) }}">@csrf
        <div class="mb-3"><label class="form-label">To</label><input class="form-control" value="{{ $client->email }}" disabled></div>
        <div class="mb-3"><label class="form-label">Subject</label><input class="form-control" name="subject" value="{{ ucfirst($type) }} {{ $number }} from BAMA" required></div>
        <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="7" required>Hello {{ $client->name }},

Please find attached {{ $type }} {{ $number }} from BAMA.

Thank you.</textarea></div>
        <button class="btn btn-warning"><i class="bi bi-send"></i> Send Email</button>
    </form>
</div></div>
@endsection
