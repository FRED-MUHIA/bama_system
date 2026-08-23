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
@endphp
<!doctype html>
<html>
<head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827}
.accent{position:fixed;top:-34px;left:-34px;right:-34px;height:26px;background:{{ $primaryColor }}}
.header{display:table;width:100%;margin-bottom:26px}
.company,.logo-wrap{display:table-cell;vertical-align:top}
.company{width:72%}
.logo-wrap{width:28%;text-align:right}
.company-name{font-size:18px;font-weight:700;color:{{ $secondaryColor }}}
.subtitle{color:{{ $primaryColor }};font-weight:700;font-size:10px}
.logo{max-height:64px;max-width:110px}
.logo-fallback{display:inline-block;width:60px;height:60px;border-radius:50%;background:{{ $secondaryColor }};color:#fff;text-align:center;line-height:60px;font-weight:700}
h1{font-size:20px;color:{{ $secondaryColor }};border-left:4px solid {{ $primaryColor }};padding-left:12px;margin-bottom:8px}
.meta{color:#6b7280;margin-bottom:20px;background:{{ $accentColor }};padding:10px 12px}
.content{white-space:pre-wrap;line-height:1.5}
</style></head>
<body>
<div class="accent"></div>
<div class="header">
    <div class="company">
        <div class="company-name">{{ $companyName }}</div>
        <div class="subtitle">Business Services</div>
        @if($settings?->address)<div>{{ $settings->address }}</div>@endif
        @if($settings?->phone || $settings?->email)<div>{{ $settings?->phone }} @if($settings?->phone && $settings?->email) | @endif {{ $settings?->email }}</div>@endif
    </div>
    <div class="logo-wrap">
        @if($settings?->logoFilePath())
            <img class="logo" src="{{ $settings->logoFilePath() }}">
        @else
            <span class="logo-fallback">LOGO</span>
        @endif
    </div>
</div>
<h1>{{ $document->title }}</h1>
<div class="meta">{{ $document->document_type }} · {{ $document->project?->project_name }} · {{ $document->project?->client?->name }}</div>
<div class="content">{{ $document->content }}</div>
</body>
</html>
