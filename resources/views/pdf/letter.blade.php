@php
    $documentColors = $settings?->documentColors() ?? [
        'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
        'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
        'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
    ];
    $primaryColor = $documentColors['primary'];
    $secondaryColor = $documentColors['secondary'];
    $accentColor = $documentColors['accent'];
    $companyName = $settings?->company_name ?? 'BAMA';
    $initials = \Illuminate\Support\Str::of($companyName)->explode(' ')->filter()->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') ?: 'BA';
    $signatory ??= null;
    $qrCode ??= '';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 34px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:10.5px; line-height:1.55; }
        .accent-strip { width:100%; height:8px; border-collapse:collapse; margin-bottom:20px; }
        .accent-strip td { padding:0; }
        .accent-primary { width:68%; background:{{ $primaryColor }}; }
        .accent-secondary { width:22%; background:{{ $secondaryColor }}; }
        .accent-soft { width:10%; background:{{ $accentColor }}; }
        .header { display:table; width:100%; margin-bottom:18px; padding-bottom:14px; border-bottom:2px solid #111827; }
        .company-info, .logo-wrap { display:table-cell; vertical-align:top; }
        .logo-wrap { width:84px; text-align:left; }
        .company-info { width:auto; }
        .company-name { color:#111827; font-size:21px; font-weight:700; margin:0 0 3px; line-height:1.08; text-transform:uppercase; }
        .company-subtitle { color:{{ $primaryColor }}; font-weight:bold; font-size:9px; letter-spacing:.8px; text-transform:uppercase; margin-bottom:7px; }
        .company-detail { color:#4b5563; font-size:10px; line-height:1.4; }
        .logo-frame { display:inline-block; width:66px; height:66px; border:1px solid #d0d7e2; text-align:center; background:#fff; }
        .logo { max-height:56px; max-width:56px; margin-top:5px; }
        .logo-fallback { display:inline-block; width:66px; height:66px; background:{{ $primaryColor }}; color:#fff; text-align:center; line-height:66px; font-size:14px; font-weight:bold; }
        .doc-meta { display:table; width:100%; margin-bottom:18px; padding:10px 0 12px; border-bottom:1px solid #e5e7eb; }
        .doc-meta-left, .doc-meta-right { display:table-cell; vertical-align:top; }
        .doc-meta-left { width:64%; }
        .doc-meta-right { width:36%; text-align:right; }
        .doc-kicker { display:inline-block; background:{{ $primaryColor }}; color:#fff; font-size:8px; font-weight:bold; letter-spacing:1.4px; text-transform:uppercase; padding:4px 8px; margin-bottom:7px; }
        .doc-title { font-size:15px; font-weight:700; color:#111827; margin-bottom:4px; line-height:1.25; }
        .meta-label { color:#6b7280; font-size:9px; text-transform:uppercase; letter-spacing:0.5px; }
        .meta-value { font-size:11px; color:#111827; font-weight:bold; }
        .recipient-block { margin-bottom:18px; border:1px solid #e5e7eb; padding:12px; }
        .recipient-label { color:#6b7280; font-size:9px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px; }
        .recipient-name { font-size:12px; font-weight:700; color:{{ $secondaryColor }}; }
        .recipient-detail { font-size:11px; color:#4b5563; line-height:1.4; }
        .subject-line { font-size:13px; font-weight:700; color:#111827; margin-bottom:18px; padding:10px 14px; background:{{ $accentColor }}; border-left:3px solid {{ $primaryColor }}; }
        .content { font-size:10.8px; line-height:1.65; color:#1f2937; margin-bottom:28px; }
        .content p { margin:0 0 10px; }
        .signature-block { margin-top:28px; padding-top:16px; border-top:1px solid #e5e7eb; page-break-inside:avoid; }
        .signature-left, .signature-right { display:table-cell; vertical-align:bottom; }
        .signature-left { width:60%; }
        .signature-right { width:40%; text-align:right; }
        .sig-img { max-height:56px; max-width:145px; margin-right:10px; margin-bottom:4px; vertical-align:bottom; }
        .stamp-img { max-height:72px; max-width:95px; margin-bottom:4px; vertical-align:bottom; }
        .sig-name { font-weight:700; font-size:12px; color:{{ $secondaryColor }}; }
        .sig-title { font-size:10px; color:#6b7280; }
        .qr-wrap { text-align:right; margin-top:16px; }
        .qr-wrap img { width:86px; height:86px; display:inline-block; border:1px solid #e5e7eb; padding:4px; }
        .qr-label { font-size:8px; color:#6b7280; margin-top:2px; }
        .footer { position:fixed; bottom:-22px; left:0; right:0; text-align:center; color:#6b7280; font-size:9px; border-top:1px solid #e5e7eb; padding-top:6px; }
    </style>
</head>
<body>
<table class="accent-strip"><tr><td class="accent-primary"></td><td class="accent-secondary"></td><td class="accent-soft"></td></tr></table>

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
        @if($settings?->address)<div class="company-detail">{{ $settings->address }}</div>@endif
        @if($settings?->location)<div class="company-detail">{{ $settings->location }}</div>@endif
        @if($settings?->phone || $settings?->email)<div class="company-detail">
            @if($settings?->phone){{ $settings->phone }}@endif
            @if($settings?->phone && $settings?->email) | @endif
            @if($settings?->email){{ $settings->email }}@endif
        </div>@endif
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

<div class="footer">{{ $companyName }} · {{ $letter->letter_number }} · Generated on {{ $letter->created_at?->format('d M Y') }}</div>
</body>
</html>
