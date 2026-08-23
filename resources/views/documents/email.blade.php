@extends('layouts.app')
@section('title','Send '.ucfirst($type))
@section('content')
@php
    $number = $type === 'quotation' ? $document->quotation_number : ($type === 'invoice' ? $document->invoice_number : $document->receipt_number);
    $client = $type === 'receipt' ? $document->invoice->client : $document->client;
    $profileName = \App\Models\CompanySetting::withoutGlobalScopes()->where('business_id', $document->business_id)->value('company_name')
        ?: \App\Models\Business::withoutGlobalScopes()->where('id', $document->business_id)->value('name')
        ?: config('app.name', 'BAMA');
    $mailSetting = \App\Models\MailSetting::withoutGlobalScopes()->where('business_id', $document->business_id)->where('enabled', true)->first();
    $usesOwnSmtp = filled($mailSetting?->username) && filled($mailSetting?->password);
@endphp
<div class="card"><div class="card-body">
    @unless($mailSetting)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Enable this profile's business email before sending {{ $type }} emails.</span>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('administration.index') }}#profile-email">Set Business Email</a>
        </div>
    @else
        <div class="alert alert-success">
            Sending as {{ $mailSetting->from_name }}{{ $usesOwnSmtp ? ' through its corporate mailbox' : ' through server mail' }}. Replies go to {{ $mailSetting->from_address }}.
        </div>
    @endunless
    <form method="post" action="{{ route($type.'s.email.send', $document) }}">@csrf
        <div class="mb-3"><label class="form-label">To</label><input class="form-control" value="{{ $client->email }}" disabled></div>
        <div class="mb-3"><label class="form-label">Subject</label><input class="form-control" name="subject" value="{{ ucfirst($type) }} {{ $number }} from {{ $profileName }}" required></div>
        <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="7" required>Hello {{ $client->name }},

Please find attached {{ $type }} {{ $number }} from {{ $profileName }}.

Thank you.</textarea></div>
        <button class="btn btn-warning"><i class="bi bi-send"></i> Send Email</button>
    </form>
</div></div>
@endsection
