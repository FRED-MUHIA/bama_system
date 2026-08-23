@php
    $settings ??= null;
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
    $logoPath = $settings?->logoFilePath();
    $ticketNumber = $job->ticket?->ticket_number ?: $job->job_number;
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size:A4 portrait; margin:24px 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color:#101828; font-size:10px; line-height:1.45; }
        .sheet { border:1px solid #d9e1ec; }
        .top-bar { width:100%; height:8px; border-collapse:collapse; }
        .top-bar td { padding:0; }
        .top-primary { width:68%; background:{{ $primaryColor }}; }
        .top-secondary { width:22%; background:{{ $secondaryColor }}; }
        .top-accent { width:10%; background:{{ $accentColor }}; }
        .inner { padding:22px 24px 24px; }
        table { width:100%; border-collapse:collapse; }
        .letterhead { margin-bottom:20px; padding-bottom:14px; border-bottom:2px solid #111827; }
        .logo-cell { width:74px; vertical-align:top; }
        .logo-frame { width:60px; height:60px; border:1px solid #d0d7e2; background:#fff; text-align:center; vertical-align:middle; }
        .logo { max-width:52px; max-height:52px; margin-top:4px; }
        .logo-fallback { width:60px; height:60px; background:{{ $primaryColor }}; color:#fff; line-height:60px; font-size:14px; font-weight:bold; }
        .company { vertical-align:top; }
        .company h1 { margin:0 0 3px; font-size:18px; color:#111827; line-height:1.1; text-transform:uppercase; }
        .subtitle { color:{{ $primaryColor }}; font-size:9px; font-weight:bold; letter-spacing:.9px; text-transform:uppercase; margin-bottom:7px; }
        .company-detail { color:#344054; font-size:9px; line-height:1.45; }
        .meta { width:32%; text-align:right; vertical-align:top; }
        .doc-type { display:inline-block; color:#fff; background:{{ $primaryColor }}; font-size:8px; font-weight:bold; letter-spacing:1.4px; text-transform:uppercase; padding:5px 8px; margin-bottom:7px; }
        .doc-number { font-size:13px; font-weight:bold; color:#111827; margin-bottom:4px; }
        .section { border:1px solid #dbe4ef; background:#f9fbfd; border-radius:7px; padding:12px; margin-bottom:12px; }
        .label { font-size:7px; font-weight:bold; letter-spacing:1.4px; text-transform:uppercase; color:#475467; margin-bottom:7px; }
        .detail-grid td { width:50%; border:1px solid #edf1f6; background:#fff; padding:9px; vertical-align:top; }
        .detail-label { color:#475467; font-size:7px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; }
        .detail-value { display:block; margin-top:3px; font-size:11px; font-weight:bold; color:#111827; word-break:break-word; }
        .specs th, .specs td { border-bottom:1px solid #e7edf4; padding:6px 0; vertical-align:top; }
        .specs th { width:34%; text-align:left; color:#475467; font-weight:bold; }
        .specs td { color:#111827; word-break:break-word; }
        .notes { white-space:pre-line; color:#111827; }
        .footer { position:fixed; bottom:-16px; left:28px; right:28px; text-align:center; color:#667085; font-size:8px; }
    </style>
</head>
<body>
<div class="sheet">
    <table class="top-bar"><tr><td class="top-primary"></td><td class="top-secondary"></td><td class="top-accent"></td></tr></table>
    <div class="inner">
        <table class="letterhead">
            <tr>
                <td class="logo-cell">
                    <div class="logo-frame">
                        @if($logoPath)
                            <img class="logo" src="{{ $logoPath }}">
                        @else
                            <div class="logo-fallback">{{ strtoupper($initials) }}</div>
                        @endif
                    </div>
                </td>
                <td class="company">
                    <h1>{{ $companyName }}</h1>
                    <div class="subtitle">Production Job Ticket</div>
                    @if($settings?->address)<div class="company-detail">{{ $settings->address }}</div>@endif
                    @if($settings?->location)<div class="company-detail">{{ $settings->location }}</div>@endif
                    @if($settings?->phone || $settings?->email)
                        <div class="company-detail">
                            @if($settings?->phone){{ $settings->phone }}@endif
                            @if($settings?->phone && $settings?->email) | @endif
                            @if($settings?->email){{ $settings->email }}@endif
                        </div>
                    @endif
                </td>
                <td class="meta">
                    <div class="doc-type">Job Ticket</div>
                    <div class="doc-number">{{ $ticketNumber }}</div>
                    <div>{{ $job->job_number }}</div>
                    <div>{{ now()->format('M d, Y') }}</div>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="label">Job details</div>
            <table class="detail-grid">
                <tr>
                    <td><span class="detail-label">Client</span><span class="detail-value">{{ $job->client?->name ?: '-' }}</span></td>
                    <td><span class="detail-label">Product</span><span class="detail-value">{{ $job->product_name ?: '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="detail-label">Quantity</span><span class="detail-value">{{ $job->quantity ?: '-' }}</span></td>
                    <td><span class="detail-label">Machine</span><span class="detail-value">{{ $job->machine?->name ?: '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="detail-label">Deadline</span><span class="detail-value">{{ $job->delivery_date?->format('d M Y') ?: '-' }}</span></td>
                    <td><span class="detail-label">Priority</span><span class="detail-value">{{ $job->priority ?: '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="detail-label">Status</span><span class="detail-value">{{ $job->status ?: '-' }}</span></td>
                    <td><span class="detail-label">Artwork</span><span class="detail-value">{{ $job->artwork_path ?: 'Pending' }}</span></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="label">Specifications</div>
            <table class="specs">
                @forelse($job->specifications ?? [] as $label => $value)
                    <tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>
                @empty
                    <tr><td colspan="2">-</td></tr>
                @endforelse
            </table>
        </div>

        <div class="section">
            <div class="label">Production notes / special instructions</div>
            <div class="notes">{{ $job->production_notes ?: '-' }}</div>
        </div>

        <div class="section">
            <div class="label">Ticket references</div>
            <table class="specs">
                <tr><th>QR token</th><td>{{ $job->ticket?->qr_token ?: '-' }}</td></tr>
                <tr><th>Barcode</th><td>{{ $job->ticket?->barcode ?: '-' }}</td></tr>
            </table>
        </div>
    </div>
</div>
<div class="footer">{{ $companyName }} - {{ $ticketNumber }} - Generated {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
