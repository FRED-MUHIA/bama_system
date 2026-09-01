@php
    $documentColors = $settings?->documentColors() ?? [
        'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
        'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
        'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
    ];
    $primaryColor = $documentColors['primary'];
    $secondaryColor = $documentColors['secondary'];
    $accentColor = $documentColors['accent'];
    $companyName = $settings?->company_name ?? 'Bama';
    $initials = \Illuminate\Support\Str::of($companyName)->explode(' ')->filter()->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') ?: 'BA';
    $signatory ??= null;
    $qrCode ??= '';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size:A4 portrait; margin: 20px 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color:#020617; font-size:10px; line-height:1.55; }
        .sheet { border:1px solid #e3e8ef; border-radius:12px; overflow:hidden; }
        .inner { padding:22px 24px 24px; }
        .accent-strip { width:100%; height:8px; border-collapse:collapse; margin-bottom:0; }
        .accent-strip td { padding:0; }
        .accent-primary { width:78%; background:{{ $primaryColor }}; }
        .accent-secondary { width:22%; background:{{ $secondaryColor }}; }
        .accent-soft { width:0; background:{{ $accentColor }}; }
        .header { display:table; width:100%; margin-bottom:24px; }
        .company-info, .logo-wrap { display:table-cell; vertical-align:top; }
        .logo-wrap { width:54px; text-align:left; }
        .company-info { width:auto; }
        .company-name { color:#020617; font-size:15px; font-weight:700; margin:0 0 4px; line-height:1.1; }
        .company-subtitle { display:none; }
        .company-detail { color:#020617; font-size:8px; line-height:1.45; }
        .logo-frame { display:inline-block; width:42px; height:42px; text-align:center; background:#fff; }
        .logo { max-height:42px; max-width:42px; }
        .logo-fallback { display:inline-block; width:42px; height:42px; border-radius:21px; background:{{ $primaryColor }}; color:#fff; text-align:center; line-height:42px; font-size:11px; font-weight:bold; }
        .doc-meta { display:table; width:100%; margin-bottom:18px; }
        .doc-meta-left, .doc-meta-right { display:table-cell; vertical-align:top; }
        .doc-meta-left { width:64%; }
        .doc-meta-right { width:36%; text-align:right; }
        .doc-kicker { color:{{ $primaryColor }}; font-size:7px; font-weight:bold; letter-spacing:4px; text-transform:uppercase; margin-bottom:4px; }
        .doc-title { font-size:13px; font-weight:700; color:#020617; margin-bottom:4px; line-height:1.25; }
        .meta-label { color:#020617; font-size:8px; }
        .meta-value { font-size:9px; color:#020617; font-weight:bold; }
        .recipient-block { margin-bottom:18px; border:1px solid #dbe3ee; background:{{ $accentColor }}; border-radius:9px; padding:12px; }
        .recipient-label { color:#020617; font-size:7px; text-transform:uppercase; letter-spacing:3px; margin-bottom:7px; font-weight:bold; }
        .recipient-name { font-size:12px; font-weight:700; color:#020617; }
        .recipient-detail { font-size:9px; color:#020617; line-height:1.45; }
        .subject-line { font-size:13px; font-weight:700; color:#020617; margin-bottom:18px; padding:10px 14px; background:{{ $accentColor }}; border:1px solid #dbe3ee; border-radius:9px; }
        .content { font-size:10px; line-height:1.7; color:#020617; margin-bottom:28px; }
        .content p { margin:0 0 10px; }
        .signature-block { margin-top:28px; padding-top:16px; border-top:1px solid #e4eaf2; page-break-inside:avoid; }
        .signature-left, .signature-right { display:table-cell; vertical-align:bottom; }
        .signature-left { width:60%; }
        .signature-right { width:40%; text-align:right; }
        .sig-img { max-height:56px; max-width:145px; margin-right:10px; margin-bottom:4px; vertical-align:bottom; }
        .stamp-img { max-height:72px; max-width:95px; margin-bottom:4px; vertical-align:bottom; }
        .sig-name { font-weight:700; font-size:12px; color:#020617; }
        .sig-title { font-size:10px; color:#020617; }
        .qr-wrap { text-align:right; margin-top:16px; }
        .qr-wrap img { width:86px; height:86px; display:inline-block; border:1px solid #e5e7eb; padding:4px; }
        .qr-label { font-size:8px; color:#020617; margin-top:2px; }
        .footer { position:fixed; bottom:-12px; left:24px; right:24px; text-align:center; color:#020617; font-size:8px; }
    </style>
</head>
<body>
<div class="sheet">
<table class="accent-strip"><tr><td class="accent-primary"></td><td class="accent-secondary"></td><td class="accent-soft"></td></tr></table>
<div class="inner">
<div class="header">
    <div class="logo-wrap">
        <div class="logo-frame">
            @if($settings?->logoFilePath())
                <img class="logo" src="{{ $settings->logoFilePath() }}">
            @else
                <span class="logo-fallback">{{ strtoupper($initials) }}</span>
            @endif
        </div>
    </div>
    <div class="company-info">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-subtitle">Business Services</div>
        @if($settings?->phone || $settings?->email)<div class="company-detail">
            @if($settings?->phone){{ $settings->phone }}@endif
            @if($settings?->phone && $settings?->email) | @endif
            @if($settings?->email){{ $settings->email }}@endif
        </div>@endif
        @if($settings?->address)<div class="company-detail">{{ $settings->address }}</div>@endif
        @if($settings?->location)<div class="company-detail">{{ $settings->location }}</div>@endif
    </div>
</div>

<div class="doc-meta">
    <div class="doc-meta-left">
        <div class="doc-kicker">Official Letter</div>
        <div class="doc-title">{{ $letter->subject }}</div>
    </div>
    <div class="doc-meta-right">
        <div><span class="meta-label">Ref:</span> <span class="meta-value">{{ $letter->letter_number }}</span></div>
        <div><span class="meta-label">Date:</span> <span class="meta-value">{{ $letter->created_at?->format('F j, Y') }}</span></div>
        <div><span class="meta-label">Type:</span> <span class="meta-value">{{ $letter->type }}</span></div>
    </div>
</div>

@if($letter->client)
<div class="recipient-block">
    <div class="recipient-label">To:</div>
    <div class="recipient-name">{{ $letter->client->name }}</div>
    @if($letter->client->company_name)<div class="recipient-detail">{{ $letter->client->company_name }}</div>@endif
    @if($letter->client->address)<div class="recipient-detail">{{ $letter->client->address }}</div>@endif
    @if($letter->client->phone || $letter->client->email)<div class="recipient-detail">
        @if($letter->client->phone){{ $letter->client->phone }}@endif
        @if($letter->client->phone && $letter->client->email) | @endif
        @if($letter->client->email){{ $letter->client->email }}@endif
    </div>@endif
</div>
@endif

@if($isRendered ?? false)
    <div class="content">{!! $renderedContent !!}</div>
@else
    <div class="content" style="white-space:pre-wrap">{{ $letter->content }}</div>
@endif

<div class="signature-block" style="display:table; width:100%;">
    <div class="signature-left">
        <div>Yours sincerely,</div>
        <br><br>
        @if($signatory)
            @if($signatory->signatureFilePath() || $signatory->stampFilePath())
                <div>
                    @if($signatory->signatureFilePath())<img class="sig-img" src="{{ $signatory->signatureFilePath() }}">@endif
                    @if($signatory->stampFilePath())<img class="stamp-img" src="{{ $signatory->stampFilePath() }}">@endif
                </div>
            @endif
            <div class="sig-name">{{ $signatory->name }}</div>
            <div class="sig-title">{{ $signatory->title }}</div>
        @else
            <div class="sig-name">{{ $companyName }}</div>
        @endif
    </div>
    @if(!empty($qrCode))
    <div class="signature-right">
        <div class="qr-wrap">
            <img src="{{ $qrCode }}">
            <div class="qr-label">Scan to verify document</div>
        </div>
    </div>
    @endif
</div>

</div>
</div>
<div class="footer">Thank you for choosing {{ $companyName }}.</div>
</body>
</html>
