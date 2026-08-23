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
        @page { margin: 34px 34px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:11px; line-height:1.35; }
        .accent-top { position:fixed; top:-34px; left:-34px; width:360px; height:28px; background:{{ $primaryColor }}; }
        .accent-top-dark { position:fixed; top:-34px; left:270px; width:130px; height:28px; background:{{ $secondaryColor }}; }
        .accent-bottom { position:fixed; bottom:-42px; right:-34px; width:360px; height:32px; background:{{ $primaryColor }}; }
        .accent-bottom-dark { position:fixed; bottom:-42px; left:-34px; width:150px; height:32px; background:{{ $secondaryColor }}; }
        .header { display:table; width:100%; margin-top:8px; margin-bottom:28px; }
        .company, .logo-wrap { display:table-cell; vertical-align:top; }
        .company { width:72%; }
        .logo-wrap { width:28%; text-align:right; }
        .company h2 { color:{{ $secondaryColor }}; font-size:20px; margin:0 0 2px; }
        .company .subtitle { color:{{ $primaryColor }}; font-weight:bold; font-size:12px; margin-bottom:6px; }
        .logo { max-height:72px; max-width:110px; }
        .logo-fallback { display:inline-block; width:72px; height:72px; border-radius:50%; background:{{ $secondaryColor }}; color:#fff; text-align:center; line-height:72px; font-size:12px; font-weight:bold; }
        .doc-title { text-align:center; margin-bottom:28px; }
        .doc-title h1 { font-size:19px; letter-spacing:2px; text-decoration:underline; margin:0; color:{{ $secondaryColor }}; }
        .doc-title div { font-size:10px; }
        .parties { display:table; width:100%; margin-bottom:24px; }
        .from, .dates { display:table-cell; vertical-align:top; width:50%; }
        .dates { text-align:right; }
        .section-label { font-weight:bold; color:{{ $secondaryColor }}; }
        table { width:100%; border-collapse:collapse; }
        .items th { background:{{ $accentColor }}; color:{{ $secondaryColor }}; padding:9px 8px; font-size:10px; text-align:center; }
        .items td { border:1px solid #d1d5db; padding:10px 8px; vertical-align:top; }
        .items .number { text-align:right; white-space:nowrap; }
        .lower { display:table; width:100%; }
        .notes, .totals-wrap { display:table-cell; vertical-align:top; }
        .notes { width:58%; padding-top:18px; padding-right:18px; }
        .totals-wrap { width:42%; }
        .totals td, .totals th { border:1px solid #d1d5db; padding:10px 8px; }
        .totals th { font-weight:normal; background:#fff; text-align:center; }
        .totals td { text-align:right; }
        .totals .grand th, .totals .grand td { font-weight:bold; color:{{ $secondaryColor }}; }
        h3 { font-size:12px; margin:0 0 4px; color:{{ $secondaryColor }}; }
        p { margin:0 0 8px; }
        .signature { margin-top:44px; }
        .signature-line { width:150px; border-top:1px solid {{ $secondaryColor }}; margin-bottom:5px; }
        .signature-assets { margin-bottom:6px; }
        .sig-img { max-height:52px; max-width:130px; margin-right:10px; vertical-align:bottom; }
        .stamp-img { max-height:68px; max-width:92px; vertical-align:bottom; }
        .sig-name { font-weight:bold; color:{{ $secondaryColor }}; }
        .sig-title { color:#6b7280; font-size:10px; }
        .footer { position:fixed; bottom:-20px; left:0; right:0; text-align:center; color:#6b7280; font-size:10px; }
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
<div class="lower">
    <div class="notes">
        @if($paymentMethods->count())
            <h3>Payment Methods</h3>
            @foreach($paymentMethods as $method)
                <p><strong>{{ $method->name }}:</strong><br>{{ $method->details }}</p>
            @endforeach
        @endif
        <h3>Terms and Conditions :</h3>
        <p>This receipt confirms payment received against the invoice shown above. Please keep it for your records.</p>
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
    <div class="totals-wrap">
        <table class="totals">
            <tr><th>Invoice Total</th><td>{{ number_format($receipt->invoice->total, 2) }}</td></tr>
            <tr><th>Amount Paid</th><td>{{ number_format($receipt->amount_paid, 2) }}</td></tr>
            <tr class="grand"><th>Balance Remaining</th><td>{{ number_format($receipt->balance_remaining, 2) }}</td></tr>
        </table>
    </div>
</div>
<div class="footer">Thank you for your payment.</div>
</body>
</html>
