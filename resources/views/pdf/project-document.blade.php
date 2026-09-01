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
@endphp
<!doctype html>
<html>
<head><meta charset="utf-8"><style>
@page{margin:32px 34px 42px}
body{font-family:DejaVu Sans,sans-serif;font-size:10.8px;color:#111827;line-height:1.55}
.accent-top{position:fixed;top:-32px;left:-34px;width:360px;height:22px;background:{{ $primaryColor }}}
.accent-top-dark{position:fixed;top:-32px;left:270px;width:130px;height:22px;background:{{ $secondaryColor }}}
.accent-bottom{position:fixed;bottom:-42px;right:-34px;width:360px;height:24px;background:{{ $primaryColor }}}
.header{display:table;width:100%;margin-top:4px;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #e5e7eb}
.company,.logo-wrap{display:table-cell;vertical-align:top}
.company{width:72%}
.logo-wrap{width:28%;text-align:right}
.company-name{font-size:20px;font-weight:700;color:{{ $secondaryColor }};line-height:1.05}
.subtitle{color:{{ $primaryColor }};font-weight:700;font-size:10px;margin-bottom:5px}
.company-detail{color:#4b5563;font-size:10px}
.logo{max-height:68px;max-width:110px}
.logo-fallback{display:inline-block;width:62px;height:62px;border-radius:50%;background:{{ $secondaryColor }};color:#fff;text-align:center;line-height:62px;font-weight:700}
h1{font-size:18px;color:{{ $secondaryColor }};margin:0}
.title-band{margin-bottom:16px;background:{{ $accentColor }};border-left:4px solid {{ $primaryColor }};padding:10px 12px}
.meta{color:#4b5563;margin-bottom:18px;border:1px solid #e5e7eb;padding:10px 12px}
.meta strong{color:{{ $secondaryColor }}}
.content{white-space:pre-wrap;line-height:1.65;border-top:1px solid #e5e7eb;padding-top:14px}
.footer{position:fixed;bottom:-22px;left:0;right:0;text-align:center;color:#6b7280;font-size:9px}
</style></head>
<body>
<div class="accent-top"></div><div class="accent-top-dark"></div><div class="accent-bottom"></div>
<div class="header">
    <div class="company">
        <div class="company-name">{{ $companyName }}</div>
        <div class="subtitle">Business Services</div>
        @if($settings?->address)<div class="company-detail">{{ $settings->address }}</div>@endif
        @if($settings?->location)<div class="company-detail">{{ $settings->location }}</div>@endif
        @if($settings?->phone || $settings?->email)<div class="company-detail">{{ $settings?->phone }} @if($settings?->phone && $settings?->email) | @endif {{ $settings?->email }}</div>@endif
    </div>
    <div class="logo-wrap">
        @if($settings?->logoFilePath())
            <img class="logo" src="{{ $settings->logoFilePath() }}">
        @else
            <span class="logo-fallback">LOGO</span>
        @endif
    </div>
</div>
<div class="title-band">
    <h1>{{ $document->title }}</h1>
</div>
<div class="meta">
    <strong>Type:</strong> {{ $document->document_type }}<br>
    <strong>Project:</strong> {{ $document->project?->project_name ?: '-' }}<br>
    <strong>Client:</strong> {{ $document->project?->client?->name ?: '-' }}
    @if($document->project?->site)<br><strong>Site:</strong> {{ $document->project->site->site_name }}@endif
</div>
<div class="content">{{ $document->content }}</div>
<div class="footer">{{ $companyName }} - {{ $document->title }}</div>
</body>
</html>
