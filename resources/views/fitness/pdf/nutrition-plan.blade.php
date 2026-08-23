<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    @php
        $documentColors = $settings?->documentColors() ?? [
            'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
            'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
            'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
        ];
        $primaryColor = $documentColors['primary'];
        $secondaryColor = $documentColors['secondary'];
        $accentColor = $documentColors['accent'];
    @endphp
    <style>
        @page { margin: 32px 34px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:10.5px; line-height:1.5; }
        .accent-top { position:fixed; top:-32px; left:-34px; width:360px; height:22px; background:{{ $primaryColor }}; }
        .accent-top-dark { position:fixed; top:-32px; left:270px; width:130px; height:22px; background:{{ $secondaryColor }}; }
        .accent-bottom { position:fixed; bottom:-42px; right:-34px; width:360px; height:24px; background:{{ $primaryColor }}; }
        .footer { position:fixed; bottom:-22px; left:0; right:0; text-align:center; color:#6b7280; font-size:9px; }
        .header { display:table; width:100%; margin-top:4px; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid #e5e7eb; }
        .company, .logo-wrap { display:table-cell; vertical-align:top; }
        .company { width:72%; }
        .logo-wrap { width:28%; text-align:right; }
        .logo { max-height:68px; max-width:110px; }
        .title-band { display:table; width:100%; margin-bottom:16px; background:{{ $accentColor }}; border-left:4px solid {{ $primaryColor }}; padding:10px 12px; }
        .title-left, .title-right { display:table-cell; vertical-align:top; }
        .title-left { width:62%; }
        .title-right { width:38%; text-align:right; }
        h1 { color:{{ $secondaryColor }}; font-size:18px; margin:0 0 4px; }
        h2 { color:{{ $secondaryColor }}; font-size:20px; margin:0 0 3px; line-height:1.05; }
        h3 { color:{{ $secondaryColor }}; font-size:11px; margin:0 0 7px; text-transform:uppercase; letter-spacing:.06em; }
        .muted { color:#6b7280; }
        .box { border:1px solid #d1d5db; padding:12px; margin-bottom:12px; }
        .metrics { width:100%; border-collapse:collapse; margin-bottom:12px; }
        .metrics td { width:25%; border:1px solid #d1d5db; padding:8px 7px; vertical-align:top; }
        .label { color:#6b7280; font-size:8.5px; text-transform:uppercase; letter-spacing:.06em; }
        .value { font-size:12px; font-weight:bold; color:{{ $secondaryColor }}; }
        .meal-notes { white-space:pre-line; }
        .signature { margin-top:34px; overflow:hidden; }
        .signature-block { float:left; width:48%; padding-top:22px; }
        .line { border-top:1px solid #111827; width:190px; margin-bottom:5px; }
    </style>
</head>
<body>
@php($companyName = $settings?->company_name ?? $business?->name ?? 'Gym')
<div class="accent-top"></div><div class="accent-top-dark"></div><div class="accent-bottom"></div>
<div class="header">
    <div class="company">
        <h2>{{ $companyName }}</h2>
        @if($settings?->address)<div>{{ $settings->address }}</div>@endif
        @if($settings?->location)<div>{{ $settings->location }}</div>@endif
        @if($settings?->phone)<div>{{ $settings->phone }}</div>@endif
        @if($settings?->email)<div>{{ $settings->email }}</div>@endif
    </div>
    <div class="logo-wrap">
        @if($settings?->logoFilePath())
            <img class="logo" src="{{ $settings->logoFilePath() }}">
        @endif
    </div>
</div>
<div class="title-band">
    <div class="title-left">
        <h1>Nutrition Plan</h1>
        <strong>{{ $assignment->member_name }}</strong><br>
        <span class="muted">{{ $assignment->member_number }}</span>
    </div>
    <div class="title-right">
        <div class="muted">Issued {{ now()->format('d M Y') }}</div>
        <div class="muted">Assignment #{{ $assignment->id }}</div>
    </div>
</div>

<div class="box">
    <h3>Member</h3>
    <strong>{{ $assignment->member_name }}</strong><br>
    {{ $assignment->member_number }}
    @if($assignment->member_phone)<br>{{ $assignment->member_phone }}@endif
    @if($assignment->member_email)<br>{{ $assignment->member_email }}@endif
    @if($assignment->emergency_contact_name || $assignment->emergency_contact_phone)
        <br><span class="muted">Emergency:</span> {{ $assignment->emergency_contact_name }} {{ $assignment->emergency_contact_phone }}
    @endif
</div>

<table class="metrics">
    <tr>
        <td><div class="label">Plan</div><div class="value">{{ $assignment->plan_name }}</div></td>
        <td><div class="label">Trainer</div><div class="value">{{ $assignment->trainer_name ?: '-' }}</div></td>
        <td><div class="label">Start Date</div><div class="value">{{ \Illuminate\Support\Carbon::parse($assignment->starts_at)->format('d M Y') }}</div></td>
        <td><div class="label">End Date</div><div class="value">{{ $assignment->ends_at ? \Illuminate\Support\Carbon::parse($assignment->ends_at)->format('d M Y') : 'Ongoing' }}</div></td>
    </tr>
    <tr>
        <td><div class="label">Calories</div><div class="value">{{ $assignment->calories ?: '-' }}</div></td>
        <td><div class="label">Protein</div><div class="value">{{ $assignment->protein ? $assignment->protein.' g' : '-' }}</div></td>
        <td><div class="label">Carbs</div><div class="value">{{ $assignment->carbohydrates ? $assignment->carbohydrates.' g' : '-' }}</div></td>
        <td><div class="label">Fat</div><div class="value">{{ $assignment->fat ? $assignment->fat.' g' : '-' }}</div></td>
    </tr>
    <tr>
        <td><div class="label">Fiber</div><div class="value">{{ $assignment->fiber ? $assignment->fiber.' g' : '-' }}</div></td>
        <td><div class="label">Water Goal</div><div class="value">{{ $assignment->water_intake_goal ? number_format($assignment->water_intake_goal).' ml' : '-' }}</div></td>
        <td><div class="label">Compliance</div><div class="value">{{ number_format($assignment->compliance_percent, 2) }}%</div></td>
        <td><div class="label">Status</div><div class="value">{{ $assignment->status }}</div></td>
    </tr>
</table>

<div class="box">
    <h3>Meal Plan</h3>
    @if($mealNotes)
        <div class="meal-notes">{{ $mealNotes }}</div>
    @else
        <div class="muted">No meal notes have been added to this plan.</div>
    @endif
</div>

@if($assignment->description)
    <div class="box">
        <h3>Recommendations</h3>
        <div class="meal-notes">{{ $assignment->description }}</div>
    </div>
@endif

@if($assignment->notes)
    <div class="box">
        <h3>Assignment Notes</h3>
        <div class="meal-notes">{{ $assignment->notes }}</div>
    </div>
@endif

<div class="signature">
    <div class="signature-block"><div class="line"></div>Trainer Signature</div>
    <div class="signature-block"><div class="line"></div>Member Signature</div>
</div>

<div class="footer">{{ $companyName }} · Fitness & Gym Nutrition Plan</div>
</body>
</html>
