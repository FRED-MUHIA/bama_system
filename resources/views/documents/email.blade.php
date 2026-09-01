@extends('layouts.app')
@section('title','Send '.ucfirst($type))
@section('content')
@php
    $number = $type === 'quotation' ? $document->quotation_number : ($type === 'invoice' ? $document->invoice_number : $document->receipt_number);
    $client = $type === 'receipt' ? $document->invoice->client : $document->client;
    $profileName = \App\Models\CompanySetting::withoutGlobalScopes()->where('business_id', $document->business_id)->value('company_name')
        ?: \App\Models\Business::withoutGlobalScopes()->where('id', $document->business_id)->value('name')
        ?: config('app.name', 'Bama');
    $mailSetting = \App\Models\MailSetting::withoutGlobalScopes()->where('business_id', $document->business_id)->where('enabled', true)->first();
    $usesOwnSmtp = filled($mailSetting?->username) && filled($mailSetting?->password);
    $serverMailDomain = config('mail.required_sender_domain') ?: config('mail.mailers.smtp.local_domain') ?: (
        str_contains((string) config('mail.from.address'), '@')
            ? \Illuminate\Support\Str::after(config('mail.from.address'), '@')
            : 'bama.co.ke'
    );
@endphp
<div class="card"><div class="card-body">
    @unless($mailSetting)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Enable this profile's business email before sending {{ $type }} emails.</span>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('administration.index') }}#profile-email">Set Business Email</a>
        </div>
    @else
        <div class="alert alert-success">
            Sending as {{ $mailSetting->from_name }} {{ $usesOwnSmtp ? '<'.$mailSetting->from_address.'> through its saved mailbox' : 'via '.$serverMailDomain.' server mail. Replies go to <'.$mailSetting->from_address.'>' }}.
        </div>
    @endunless
    <form method="post" action="{{ route($type.'s.email.send', $document) }}">@csrf
        <div class="mb-3"><label class="form-label">To</label><input class="form-control" name="to" type="email" value="{{ old('to', $client->email) }}" required></div>
        <div class="mb-3"><label class="form-label">CC</label><input class="form-control" name="cc" value="{{ old('cc') }}" placeholder="name@example.com, accounts@example.com"></div>
        <div class="mb-3"><label class="form-label">Subject</label><input class="form-control" name="subject" value="{{ old('subject', ucfirst($type).' '.$number.' from '.$profileName) }}" required></div>
        <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="7" required>{{ old('message', "Hello {$client->name},\n\nPlease find attached {$type} {$number} from {$profileName}.\n\nThank you.") }}</textarea></div>
        <button class="btn btn-warning"><i class="bi bi-send"></i> Send Email</button>
    </form>
</div></div>
@endsection
