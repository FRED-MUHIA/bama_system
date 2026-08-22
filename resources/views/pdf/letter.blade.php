<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 34px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:11px; line-height:1.5; }
        .accent-top { position:fixed; top:-34px; left:-34px; width:360px; height:28px; background:#f97316; }
        .accent-top-dark { position:fixed; top:-34px; left:270px; width:130px; height:28px; background:#431407; }
        .accent-bottom { position:fixed; bottom:-42px; right:-34px; width:360px; height:32px; background:#f97316; }
        .accent-bottom-dark { position:fixed; bottom:-42px; left:-34px; width:150px; height:32px; background:#431407; }
        .header { display:table; width:100%; margin-top:8px; margin-bottom:24px; }
        .company-info, .logo-wrap { display:table-cell; vertical-align:top; }
        .company-info { width:72%; }
        .logo-wrap { width:28%; text-align:right; }
        .company-name { color:#9a3412; font-size:18px; font-weight:700; margin:0 0 2px; }
        .company-subtitle { color:#f97316; font-weight:bold; font-size:10px; margin-bottom:4px; }
        .company-detail { color:#4b5563; font-size:10px; line-height:1.4; }
        .logo { max-height:72px; max-width:110px; }
        .logo-fallback { display:inline-block; width:72px; height:72px; border-radius:50%; background:#431407; color:#fff; text-align:center; line-height:72px; font-size:12px; font-weight:bold; }
        .doc-meta { display:table; width:100%; margin-bottom:20px; border-bottom:1px solid #e5e7eb; padding-bottom:10px; }
        .doc-meta-left, .doc-meta-right { display:table-cell; vertical-align:top; width:50%; }
        .doc-meta-right { text-align:right; }
        .doc-title { font-size:16px; font-weight:700; color:#111827; margin-bottom:4px; }
        .meta-label { color:#6b7280; font-size:9px; text-transform:uppercase; letter-spacing:0.5px; }
        .meta-value { font-size:11px; color:#111827; }
        .recipient-block { margin-bottom:22px; }
        .recipient-label { color:#6b7280; font-size:9px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px; }
        .recipient-name { font-size:12px; font-weight:700; color:#111827; }
        .recipient-detail { font-size:11px; color:#4b5563; line-height:1.4; }
        .subject-line { font-size:13px; font-weight:700; color:#111827; margin-bottom:18px; padding:10px 14px; background:#f9fafb; border-left:3px solid #f97316; }
        .content { font-size:11px; line-height:1.6; color:#1f2937; margin-bottom:28px; }
        .content p { margin:0 0 10px; }
        .signature-block { margin-top:36px; padding-top:20px; border-top:1px solid #e5e7eb; }
        .signature-left, .signature-right { display:table-cell; vertical-align:bottom; }
        .signature-left { width:60%; }
        .signature-right { width:40%; text-align:right; }
        .sig-img { max-height:60px; max-width:160px; margin-bottom:4px; }
        .sig-name { font-weight:700; font-size:12px; color:#111827; }
        .sig-title { font-size:10px; color:#6b7280; }
        .qr-wrap { text-align:right; margin-top:16px; }
        .qr-wrap img { width:86px; height:86px; display:inline-block; border:1px solid #e5e7eb; padding:4px; }
        .qr-label { font-size:8px; color:#6b7280; margin-top:2px; }
        .footer { position:fixed; bottom:-20px; left:0; right:0; text-align:center; color:#6b7280; font-size:9px; }
    </style>
</head>
<body>
<div class="accent-top"></div><div class="accent-top-dark"></div><div class="accent-bottom"></div><div class="accent-bottom-dark"></div>

@php
    $companyName = $settings?->company_name ?? 'BAMA';
    $signatory ??= null;
    $qrCode ??= '';
@endphp

<div class="header">
    <div class="company-info">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-subtitle">Business Services</div>
        @if($settings?->address)<div class="company-detail">{{ $settings->address }}</div>@endif
        @if($settings?->phone || $settings?->email)<div class="company-detail">
            @if($settings?->phone){{ $settings->phone }}@endif
            @if($settings?->phone && $settings?->email) | @endif
            @if($settings?->email){{ $settings->email }}@endif
        </div>@endif
    </div>
    <div class="logo-wrap">
        @if($settings?->logoFilePath())
            <img class="logo" src="{{ $settings->logoFilePath() }}">
        @else
            <span class="logo-fallback">LOGO<br>HERE</span>
        @endif
    </div>
</div>

<div class="doc-meta">
    <div class="doc-meta-left">
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
            @if($signatory->signatureFilePath())
                <div><img class="sig-img" src="{{ $signatory->signatureFilePath() }}"></div>
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
