@php
    $documentColors = $settings?->documentColors() ?? [
        'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
        'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
        'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
    ];
    $primaryColor = $documentColors['primary'];
    $secondaryColor = $documentColors['secondary'];
    $accentColor = $documentColors['accent'];
    $signatory ??= null;
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 34px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:10.5px; line-height:1.42; }
        .accent-top { position:fixed; top:-32px; left:-34px; width:360px; height:22px; background:{{ $primaryColor }}; }
        .accent-top-dark { position:fixed; top:-32px; left:270px; width:130px; height:22px; background:{{ $secondaryColor }}; }
        .accent-bottom { position:fixed; bottom:-42px; right:-34px; width:360px; height:24px; background:{{ $primaryColor }}; }
        .accent-bottom-dark { position:fixed; bottom:-42px; left:-34px; width:150px; height:24px; background:{{ $secondaryColor }}; }
        .header { display:table; width:100%; margin-top:4px; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid #e5e7eb; }
        .company, .logo-wrap { display:table-cell; vertical-align:top; }
        .company { width:72%; }
        .logo-wrap { width:28%; text-align:right; }
        .company h2 { color:{{ $secondaryColor }}; font-size:21px; margin:0 0 3px; line-height:1.05; }
        .company .subtitle { color:{{ $primaryColor }}; font-weight:bold; font-size:12px; margin-bottom:6px; }
        .company div { color:#4b5563; }
        .logo { max-height:72px; max-width:110px; }
        .logo-fallback { display:inline-block; width:72px; height:72px; border-radius:50%; background:{{ $secondaryColor }}; color:#fff; text-align:center; line-height:72px; font-size:12px; font-weight:bold; }
        .doc-title { display:table; width:100%; margin-bottom:18px; background:{{ $accentColor }}; border-left:4px solid {{ $primaryColor }}; padding:10px 12px; }
        .doc-title h1 { font-size:18px; letter-spacing:1px; margin:0 0 3px; color:{{ $secondaryColor }}; }
        .doc-title div { font-size:10px; color:#374151; }
        .parties { display:table; width:100%; margin-bottom:18px; border:1px solid #e5e7eb; }
        .from, .dates { display:table-cell; vertical-align:top; width:50%; padding:12px; }
        .dates { text-align:right; border-left:1px solid #e5e7eb; }
        .section-label { font-weight:bold; color:{{ $secondaryColor }}; }
        table { width:100%; border-collapse:collapse; }
        .items th { background:{{ $accentColor }}; color:{{ $secondaryColor }}; padding:8px; font-size:9.5px; text-align:left; border:1px solid #d1d5db; }
        .items th:nth-child(1) { width:52%; }
        .items th:nth-child(2) { width:24%; }
        .items th:nth-child(3) { width:24%; text-align:right; }
        .items td { border:1px solid #d1d5db; padding:8px; vertical-align:top; }
        .items .number { text-align:right; white-space:nowrap; }
        .totals-wrap { width:42%; margin-left:auto; margin-top:12px; page-break-inside:avoid; }
        .totals td, .totals th { border:1px solid #d1d5db; padding:8px; }
        .totals th { font-weight:normal; background:#fff; text-align:left; }
        .totals td { text-align:right; }
        .totals .grand th, .totals .grand td { font-weight:bold; color:{{ $secondaryColor }}; background:{{ $accentColor }}; }
        .notes { margin-top:18px; page-break-inside:avoid; }
        .notes h3 { font-size:11px; margin:0 0 5px; color:{{ $secondaryColor }}; text-transform:uppercase; letter-spacing:.04em; }
        .note-grid { display:table; width:100%; }
        .note-col { display:table-cell; width:50%; vertical-align:top; padding-right:18px; }
        p { margin:0 0 7px; }
        .signature { margin-top:26px; page-break-inside:avoid; }
        .signature-line { width:150px; border-top:1px solid {{ $secondaryColor }}; margin-bottom:5px; }
        .signature-assets { margin-bottom:6px; }
        .sig-img { max-height:52px; max-width:130px; margin-right:10px; vertical-align:bottom; }
        .stamp-img { max-height:68px; max-width:92px; vertical-align:bottom; }
        .sig-name { font-weight:bold; color:{{ $secondaryColor }}; }
        .sig-title { color:#6b7280; font-size:10px; }
        .footer { position:fixed; bottom:-22px; left:0; right:0; text-align:center; color:#6b7280; font-size:9px; }
    </style>
</head>
<body>
@php($companyName = $settings?->company_name ?? 'BAMA')
<div class="accent-top"></div><div class="accent-top-dark"></div><div class="accent-bottom"></div><div class="accent-bottom-dark"></div>
<div class="header">
    <div class="company">
        <h2>{{ $companyName }}</h2>
        <div class="subtitle">Business Services</div>
        @if($settings?->address)<div>{{ $settings->address }}</div>@endif
        @if($settings?->location)<div>{{ $settings->location }}</div>@endif
        @if($settings?->phone)<div>{{ $settings->phone }}</div>@endif
        @if($settings?->email)<div>{{ $settings->email }}</div>@endif
        @if($settings?->website)<div>{{ $settings->website }}</div>@endif
    </div>
    <div class="logo-wrap">
        @if($settings?->logoFilePath())
            <img class="logo" src="{{ $settings->logoFilePath() }}">
        @else
            <span class="logo-fallback">LOGO<br>HERE</span>
        @endif
    </div>
</div>
<div class="doc-title">
    <h1>RECEIPT</h1>
    <div>Receipt Number: {{ $receipt->receipt_number }}</div>
</div>
<div class="parties">
    <div class="from">
        <div class="section-label">Received from:</div>
        <strong>{{ $receipt->invoice->client->name }}</strong><br>
        {{ $receipt->invoice->client->company_name }}<br>
        {{ $receipt->invoice->client->address }}<br>
        {{ $receipt->invoice->client->phone }} {{ $receipt->invoice->client->email }}
    </div>
    <div class="dates">
        <div><span class="section-label">Payment Date:</span><br>{{ $receipt->payment_date?->format('F j, Y') }}</div>
        <br>
        <div><span class="section-label">Invoice:</span><br>{{ $receipt->invoice->invoice_number }}</div>
    </div>
</div>
<table class="items">
    <thead><tr><th>Payment Description</th><th>Payment Method</th><th>Amount</th></tr></thead>
    <tbody>
        <tr>
            <td>Payment received for invoice {{ $receipt->invoice->invoice_number }}</td>
            <td>{{ $receipt->payment_method ?: '-' }}</td>
            <td class="number">{{ number_format($receipt->amount_paid, 2) }}</td>
        </tr>
    </tbody>
</table>
<div class="totals-wrap">
    <table class="totals">
        <tr><th>Invoice Total</th><td>{{ number_format($receipt->invoice->total, 2) }}</td></tr>
        <tr><th>Amount Paid</th><td>{{ number_format($receipt->amount_paid, 2) }}</td></tr>
        <tr class="grand"><th>Balance Remaining</th><td>{{ number_format($receipt->balance_remaining, 2) }}</td></tr>
    </table>
</div>
<div class="notes">
    <div class="note-grid">
        <div class="note-col">
            @if($paymentMethods->count())
                <h3>Payment Methods</h3>
                @foreach($paymentMethods as $method)
                    <p><strong>{{ $method->name }}:</strong><br>{{ $method->details }}</p>
                @endforeach
            @endif
        </div>
        <div class="note-col">
            <h3>Receipt Note</h3>
            <p>This receipt confirms payment received against the invoice shown above. Please keep it for your records.</p>
        </div>
    </div>
    <div class="signature">
        @if($signatory?->signatureFilePath() || $signatory?->stampFilePath())
            <div class="signature-assets">
                @if($signatory?->signatureFilePath())<img class="sig-img" src="{{ $signatory->signatureFilePath() }}">@endif
                @if($signatory?->stampFilePath())<img class="stamp-img" src="{{ $signatory->stampFilePath() }}">@endif
            </div>
        @else
            <div class="signature-line"></div>
        @endif
        <span class="sig-name">{{ $signatory?->name ?? $companyName }}</span><br>
        <span class="sig-title">{{ $signatory?->title ?? 'Authorized Representative' }}</span>
    </div>
</div>
<div class="footer">Thank you for your payment.</div>
</body>
</html>
