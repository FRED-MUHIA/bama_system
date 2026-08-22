<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 34px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:11px; line-height:1.45; }
        .accent { position:fixed; top:-34px; left:-34px; right:-34px; height:22px; background:#f97316; }
        .footer { position:fixed; bottom:-22px; left:0; right:0; text-align:center; color:#6b7280; font-size:9px; }
        .header { width:100%; margin-bottom:22px; overflow:hidden; }
        .company { float:left; width:62%; }
        .meta { float:right; width:36%; text-align:right; }
        h1 { color:#111827; font-size:21px; margin:0 0 4px; }
        h2 { color:#9a3412; font-size:16px; margin:0 0 3px; }
        h3 { color:#111827; font-size:12px; margin:0 0 7px; text-transform:uppercase; letter-spacing:.08em; }
        .muted { color:#6b7280; }
        .box { border:1px solid #d1d5db; padding:12px; margin-bottom:12px; border-radius:6px; }
        .metrics { margin:0 -4px 12px; }
        .metric { display:inline-block; width:23.2%; min-height:48px; border:1px solid #d1d5db; padding:8px 6px; margin:0 3px 7px; vertical-align:top; }
        .label { color:#6b7280; font-size:9px; text-transform:uppercase; letter-spacing:.08em; }
        .value { font-size:14px; font-weight:bold; color:#111827; }
        .meal-notes { white-space:pre-line; }
        .signature { margin-top:34px; overflow:hidden; }
        .signature-block { float:left; width:48%; padding-top:22px; }
        .line { border-top:1px solid #111827; width:190px; margin-bottom:5px; }
    </style>
</head>
<body>
@php($companyName = $settings?->company_name ?? $business?->name ?? 'Gym')
<div class="accent"></div>
<div class="header">
    <div class="company">
        <h2>{{ $companyName }}</h2>
        @if($settings?->address)<div>{{ $settings->address }}</div>@endif
        @if($settings?->phone)<div>{{ $settings->phone }}</div>@endif
        @if($settings?->email)<div>{{ $settings->email }}</div>@endif
    </div>
    <div class="meta">
        <h1>Nutrition Plan</h1>
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

<div class="metrics">
    <div class="metric"><div class="label">Plan</div><div class="value">{{ $assignment->plan_name }}</div></div>
    <div class="metric"><div class="label">Trainer</div><div class="value">{{ $assignment->trainer_name ?: '-' }}</div></div>
    <div class="metric"><div class="label">Start Date</div><div class="value">{{ \Illuminate\Support\Carbon::parse($assignment->starts_at)->format('d M Y') }}</div></div>
    <div class="metric"><div class="label">End Date</div><div class="value">{{ $assignment->ends_at ? \Illuminate\Support\Carbon::parse($assignment->ends_at)->format('d M Y') : 'Ongoing' }}</div></div>
    <div class="metric"><div class="label">Calories</div><div class="value">{{ $assignment->calories ?: '-' }}</div></div>
    <div class="metric"><div class="label">Protein</div><div class="value">{{ $assignment->protein ? $assignment->protein.' g' : '-' }}</div></div>
    <div class="metric"><div class="label">Carbs</div><div class="value">{{ $assignment->carbohydrates ? $assignment->carbohydrates.' g' : '-' }}</div></div>
    <div class="metric"><div class="label">Fat</div><div class="value">{{ $assignment->fat ? $assignment->fat.' g' : '-' }}</div></div>
    <div class="metric"><div class="label">Fiber</div><div class="value">{{ $assignment->fiber ? $assignment->fiber.' g' : '-' }}</div></div>
    <div class="metric"><div class="label">Water Goal</div><div class="value">{{ $assignment->water_intake_goal ? number_format($assignment->water_intake_goal).' ml' : '-' }}</div></div>
    <div class="metric"><div class="label">Compliance</div><div class="value">{{ number_format($assignment->compliance_percent, 2) }}%</div></div>
    <div class="metric"><div class="label">Status</div><div class="value">{{ $assignment->status }}</div></div>
</div>

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
