@extends('layouts.app')
@section('title', 'Membership Card')

@section('content')
@php
    $gymName = $cardBusiness?->name ?? $activeBusiness?->name ?? config('app.name', 'Gym');
    $memberName = $member->client?->name ?: $member->client?->company_name ?: 'Member';
@endphp
<style>
    .fitness-card-wrap{max-width:900px;margin:0 auto}
    .membership-card{width:520px;max-width:100%;aspect-ratio:1.58;background:#071B12;color:#fff;border-radius:18px;padding:28px;box-shadow:0 18px 48px rgba(15,23,42,.24);position:relative;overflow:hidden}
    .membership-card:before{content:"";position:absolute;inset:auto -90px -140px auto;width:280px;height:280px;border-radius:50%;background:#00A651;opacity:.9}
    .membership-card:after{content:"";position:absolute;inset:-110px auto auto -120px;width:270px;height:270px;border-radius:50%;background:#79D9A3;opacity:.24}
    .membership-card-inner{position:relative;z-index:1;display:grid;grid-template-columns:1fr 154px;gap:18px;height:100%}
    .card-kicker{font-size:.72rem;text-transform:uppercase;letter-spacing:.14em;color:#b7f7d3;font-weight:800;overflow-wrap:anywhere}
    .member-name{font-size:1.65rem;font-weight:900;line-height:1.08;margin:.35rem 0 .15rem;overflow-wrap:anywhere}
    .member-meta{color:#d0d5dd;font-size:.88rem;overflow-wrap:anywhere}
    .card-chip{width:46px;height:34px;border-radius:8px;background:linear-gradient(135deg,#fef3c7,#f59e0b);margin:24px 0}
    .card-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:auto}
    .card-field span{display:block;color:#98a2b3;font-size:.68rem;text-transform:uppercase;font-weight:800;letter-spacing:.07em}
    .card-field strong{display:block;font-size:.92rem;line-height:1.2}
    .qr-panel{align-self:center;background:#fff;color:#111827;border-radius:14px;padding:12px;text-align:center}
    .qr-panel img{width:128px;height:128px;display:block;margin:0 auto 8px}
    .qr-panel strong{font-size:.82rem;word-break:break-all}
    .print-actions{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
    .print-hidden{display:block}
    @media print{
        @page{size:85mm 55mm;margin:0}
        html,body{width:85mm!important;height:55mm!important;margin:0!important;padding:0!important;background:#fff!important;overflow:hidden!important}
        body *{visibility:hidden!important}
        .membership-card,.membership-card *{visibility:visible!important}
        header,.sidebar,.mobile-bottom-nav,.print-actions,.print-hidden{display:none!important}
        main,section,.fitness-card-wrap{display:block!important;position:static!important;width:85mm!important;height:55mm!important;max-width:none!important;margin:0!important;padding:0!important;overflow:hidden!important}
        .membership-card{
            position:fixed!important;
            inset:0!important;
            width:85mm!important;
            height:55mm!important;
            max-width:none!important;
            aspect-ratio:auto!important;
            border-radius:3mm!important;
            padding:4mm!important;
            box-shadow:none!important;
            print-color-adjust:exact;
            -webkit-print-color-adjust:exact;
        }
        .membership-card:before{width:36mm;height:36mm;inset:auto -11mm -18mm auto}
        .membership-card:after{width:38mm;height:38mm;inset:-18mm auto auto -18mm}
        .membership-card-inner{grid-template-columns:minmax(0,1fr) 23mm;gap:3mm;height:47mm}
        .membership-card-inner>.d-flex{min-height:47mm}
        .card-kicker{font-size:6pt;letter-spacing:.1em;line-height:1.1;max-width:52mm}
        .member-name{font-size:11pt;line-height:1.05;margin:2mm 0 .7mm;max-width:50mm}
        .member-meta{font-size:6.6pt;line-height:1.15;max-width:50mm}
        .card-chip{width:10mm;height:7mm;border-radius:1.8mm;margin:5.5mm 0 3mm}
        .card-fields{gap:2mm 3mm;margin-top:auto;align-items:start}
        .card-field span{font-size:4.8pt;line-height:1.05;letter-spacing:.05em}
        .card-field strong{font-size:6.5pt;line-height:1.08;overflow-wrap:anywhere}
        .qr-panel{border-radius:2.8mm;padding:2.4mm;align-self:center}
        .qr-panel img{width:16mm;height:16mm;margin-bottom:1.2mm}
        .qr-panel .small{font-size:4.8pt!important;line-height:1.05}
        .qr-panel strong{font-size:5.6pt;line-height:1.15}
    }
</style>

<div class="fitness-card-wrap">
    <div class="print-actions mb-3">
        <a class="btn btn-outline-dark" href="{{ route('fitness.members.index') }}"><i class="bi bi-arrow-left me-1"></i>Members</a>
        <button class="btn btn-warning" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Card</button>
    </div>

    <div class="membership-card mx-auto">
        <div class="membership-card-inner">
            <div class="d-flex flex-column">
                <div class="card-kicker">{{ $gymName }}</div>
                <div class="member-meta text-uppercase fw-bold">Fitness Membership Card</div>
                <div class="member-name">{{ $memberName }}</div>
                <div class="member-meta">{{ $membership?->plan?->name ?? 'No active plan' }} · {{ $member->status }}</div>
                <div class="card-chip"></div>
                <div class="card-fields">
                    <div class="card-field"><span>Member ID</span><strong>{{ $member->member_number }}</strong></div>
                    <div class="card-field"><span>Membership</span><strong>{{ $membership?->membership_number ?? '-' }}</strong></div>
                    <div class="card-field"><span>Expires</span><strong>{{ $membership?->ends_at?->format('d M Y') ?? '-' }}</strong></div>
                    <div class="card-field"><span>Trainer</span><strong>{{ $member->assignedTrainer?->name ?: 'Unassigned' }}</strong></div>
                </div>
            </div>
            <div class="qr-panel">
                <img src="{{ $qrCode }}" alt="Member QR code">
                <span class="d-block text-muted small fw-bold text-uppercase">Scan Code</span>
                <strong>{{ $member->qr_code }}</strong>
            </div>
        </div>
    </div>

    <div class="card p-3 mt-3 print-hidden">
        <h2 class="h6 mb-2">Check-In Identifiers</h2>
        <div class="row g-2">
            <div class="col-md-4"><div class="border rounded p-2"><div class="small text-muted">Member ID</div><strong>{{ $member->member_number }}</strong></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><div class="small text-muted">QR / Scan Code</div><strong class="text-break">{{ $member->qr_code }}</strong></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><div class="small text-muted">Status</div><strong>{{ $member->status }}</strong></div></div>
        </div>
    </div>
</div>
@endsection
