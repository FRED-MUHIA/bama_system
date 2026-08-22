<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 34px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:11px; line-height:1.35; }
        .accent-top { position:fixed; top:-34px; left:-34px; width:360px; height:28px; background:#f97316; }
        .accent-top-dark { position:fixed; top:-34px; left:270px; width:130px; height:28px; background:#431407; }
        .accent-bottom { position:fixed; bottom:-42px; right:-34px; width:360px; height:32px; background:#f97316; }
        .accent-bottom-dark { position:fixed; bottom:-42px; left:-34px; width:150px; height:32px; background:#431407; }
        .header { display:table; width:100%; margin-top:8px; margin-bottom:28px; }
        .company, .logo-wrap { display:table-cell; vertical-align:top; }
        .company { width:72%; }
        .logo-wrap { width:28%; text-align:right; }
        .company h2 { color:#9a3412; font-size:20px; margin:0 0 2px; }
        .company .subtitle { color:#f97316; font-weight:bold; font-size:12px; margin-bottom:6px; }
        .logo { max-height:72px; max-width:110px; }
        .logo-fallback { display:inline-block; width:72px; height:72px; border-radius:50%; background:#431407; color:#fff; text-align:center; line-height:72px; font-size:12px; font-weight:bold; }
        .verify-box { margin-top:10px; text-align:right; font-size:9px; color:#4b5563; }
        .verify-box img { width:86px; height:86px; display:inline-block; border:1px solid #e5e7eb; padding:4px; }
        .doc-title { text-align:center; margin-bottom:28px; }
        .doc-title h1 { font-size:19px; letter-spacing:2px; text-decoration:underline; margin:0; color:#111827; }
        .doc-title div { font-size:10px; }
        .parties { display:table; width:100%; margin-bottom:24px; }
        .bill-to, .dates { display:table-cell; vertical-align:top; width:50%; }
        .dates { text-align:right; }
        .section-label { font-weight:bold; color:#111827; }
        table { width:100%; border-collapse:collapse; }
        .items th { background:#9ca3af; color:#111827; padding:9px 8px; font-size:10px; text-align:center; }
        .items td { border:1px solid #d1d5db; padding:9px 8px; vertical-align:top; }
        .items .text { text-align:left; }
        .items .number { text-align:center; white-space:nowrap; }
        .lower { display:table; width:100%; margin-top:0; }
        .notes, .totals-wrap { display:table-cell; vertical-align:top; }
        .notes { width:58%; padding-top:18px; padding-right:18px; }
        .totals-wrap { width:42%; }
        .totals td, .totals th { border:1px solid #d1d5db; padding:10px 8px; }
        .totals th { font-weight:normal; background:#fff; text-align:center; }
        .totals td { text-align:right; }
        .totals .grand th, .totals .grand td { font-weight:bold; color:#111827; }
        h3 { font-size:12px; margin:0 0 4px; color:#111827; }
        p { margin:0 0 8px; }
        .signature { margin-top:44px; }
        .signature-line { width:150px; border-top:1px solid #111827; margin-bottom:5px; }
        .footer { position:fixed; bottom:-20px; left:0; right:0; text-align:center; color:#6b7280; font-size:10px; }
    </style>
</head>
<body>
@php
    $companyName = $settings?->company_name ?? 'BAMA';
    $isPartPaymentInvoice = $type === 'Invoice' && $document->isAllocationInvoice();
    $number = $type === 'Quotation' ? $document->quotation_number : $document->invoice_number;
    $date = $type === 'Quotation' ? $document->quotation_date : $document->invoice_date;
    $secondaryDateLabel = $type === 'Quotation' ? 'Valid Until' : 'Due Date';
    $secondaryDate = $type === 'Quotation' ? $document->valid_until : $document->due_date;
    $issuerProfile = $type === 'Invoice' ? ($document->issuer_profile ?? []) : [];
    $recipientProfile = $type === 'Invoice' ? ($document->recipient_profile ?? []) : [];
    $industryContext = $type === 'Invoice' ? ($document->industry_context ?? []) : [];
    $companyName = $issuerProfile['name'] ?? $companyName;
    $companySubtitle = $issuerProfile['subtitle'] ?? 'Business Services';
@endphp
<div class="accent-top"></div><div class="accent-top-dark"></div><div class="accent-bottom"></div><div class="accent-bottom-dark"></div>
<div class="header">
    <div class="company">
        <h2>{{ $companyName }}</h2>
        <div class="subtitle">{{ $companySubtitle }}</div>
        @if($issuerProfile['address'] ?? $settings?->address)<div>{{ $issuerProfile['address'] ?? $settings?->address }}</div>@endif
        @if($issuerProfile['phone'] ?? $settings?->phone)<div>{{ $issuerProfile['phone'] ?? $settings?->phone }}</div>@endif
        @if($issuerProfile['email'] ?? $settings?->email)<div>{{ $issuerProfile['email'] ?? $settings?->email }}</div>@endif
        @if($issuerProfile['website'] ?? $settings?->website)<div>{{ $issuerProfile['website'] ?? $settings?->website }}</div>@endif
    </div>
    <div class="logo-wrap">
        @if($settings?->logoFilePath())
            <img class="logo" src="{{ $settings->logoFilePath() }}">
        @else
            <span class="logo-fallback">LOGO<br>HERE</span>
        @endif
        @if($type === 'Invoice' && !empty($qrCode))
            <div class="verify-box">
                <img src="{{ $qrCode }}"><br>
                Scan to verify invoice
            </div>
        @endif
    </div>
</div>
<div class="doc-title">
    <h1>{{ $isPartPaymentInvoice ? 'PART PAYMENT INVOICE' : strtoupper($type) }}</h1>
    <div>{{ $type }} Number: {{ $number }}</div>
    @if($type === 'Invoice' && $document->industry_reference)<div>Reference Code: {{ $document->industry_reference }}</div>@endif
    @if($isPartPaymentInvoice && $document->parentInvoice)<div>Parent Invoice: {{ $document->parentInvoice->invoice_number }}</div>@endif
</div>
<div class="parties">
    <div class="bill-to">
        <div class="section-label">Bill to:</div>
        <strong>{{ $recipientProfile['name'] ?? $document->client->name }}</strong><br>
        {{ $document->client->company_name }}<br>
        {{ $recipientProfile['address'] ?? $document->client->address }}<br>
        {{ $recipientProfile['phone'] ?? $document->client->phone }} {{ $recipientProfile['email'] ?? $document->client->email }}
        @if($type === 'Invoice' && $document->industry_module === 'real_estate')
            <br><br>
            @if(!empty($recipientProfile['tenant_number']))Tenant: {{ $recipientProfile['tenant_number'] }}<br>@endif
            @if(!empty($recipientProfile['id_number']))ID Number: {{ $recipientProfile['id_number'] }}<br>@endif
            @if(!empty($recipientProfile['passport_number']))Passport: {{ $recipientProfile['passport_number'] }}<br>@endif
            @if(!empty($industryContext['property_name']))Property: {{ $industryContext['property_name'] }} @if(!empty($industryContext['property_code']))({{ $industryContext['property_code'] }})@endif<br>@endif
            @if(!empty($industryContext['unit_number']))Unit: {{ $industryContext['unit_number'] }} @if(!empty($industryContext['unit_type']))- {{ $industryContext['unit_type'] }}@endif<br>@endif
            @if(!empty($industryContext['lease_number']))Lease: {{ $industryContext['lease_number'] }}<br>@endif
            @if(!empty($industryContext['source_reference']))Source: {{ $industryContext['source_reference'] }}@endif
        @endif
        @if($type === 'Invoice' && $document->industry_module === 'printing_branding')
            <br><br>
            @if(!empty($industryContext['job_number']))Job: {{ $industryContext['job_number'] }}<br>@endif
            @if(!empty($industryContext['ticket_number']))Ticket: {{ $industryContext['ticket_number'] }}<br>@endif
            @if(!empty($industryContext['invoice_type']))Invoice Type: {{ $industryContext['invoice_type'] }}<br>@endif
            @if(!empty($industryContext['product_name']))Product: {{ $industryContext['product_name'] }}<br>@endif
            @if(isset($industryContext['quantity']))Quantity: {{ number_format((float) $industryContext['quantity'], 3) }}<br>@endif
            @if(!empty($industryContext['delivery_date']))Delivery Date: {{ $industryContext['delivery_date'] }}<br>@endif
            @if(!empty($industryContext['job_status']))Production Status: {{ $industryContext['job_status'] }}@endif
        @endif
        @if($document->relationLoaded('project') && $document->project)<br><br><strong>Project:</strong> {{ $document->project->project_name }}@endif
        @if($document->relationLoaded('site') && $document->site)<br><strong>Site:</strong> {{ $document->site->site_name }}@endif
    </div>
    <div class="dates">
        <div><span class="section-label">{{ $secondaryDateLabel }}:</span><br>{{ $secondaryDate?->format('F j, Y') ?: '-' }}</div>
        <br>
        <div><span class="section-label">Date:</span><br>{{ $date?->format('F j, Y') }}</div>
    </div>
</div>
<table class="items">
    <thead><tr><th>Item Description</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
    <tbody>
    @foreach($document->items as $item)
        <tr>
            <td class="text"><strong>{{ $item->title ?: $item->description }}</strong>@if($item->title)<br>{{ $item->description }}@endif</td>
            <td class="number">{{ number_format($item->unit_price, 2) }}</td>
            <td class="number">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
            <td class="number">{{ number_format($item->line_total, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="lower">
    <div class="notes">
        @if($paymentMethods->count())
            <h3>Payment Method</h3>
            @foreach($paymentMethods as $method)
                <p><strong>{{ $method->name }}:</strong><br>{{ $method->details }}</p>
            @endforeach
        @endif
        @if($document->terms)
            <h3>Terms and Conditions :</h3>
            <p>{{ $document->terms }}</p>
        @endif
        <div class="signature">
            <div class="signature-line"></div>
            <strong>{{ $companyName }}</strong><br>
            <span>Authorized Representative</span>
        </div>
    </div>
    <div class="totals-wrap">
        <table class="totals">
            @if($isPartPaymentInvoice)
                <tr class="grand"><th>Allocated Amount</th><td>{{ number_format($document->part_payment_amount, 2) }}</td></tr>
                @if($document->parentInvoice)<tr><th>Source Invoice Total</th><td>{{ number_format($document->parentInvoice->total, 2) }}</td></tr>@endif
            @else
                <tr><th>Subtotal</th><td>{{ number_format($document->subtotal, 2) }}</td></tr>
                <tr><th>Tax</th><td>{{ $document->tax_total > 0 ? number_format($document->tax_total, 2) : '-' }}</td></tr>
                <tr><th>Discount</th><td>{{ $document->discount_total > 0 ? number_format($document->discount_total, 2) : '-' }}</td></tr>
            @endif
            @if($type === 'Invoice' && ! $isPartPaymentInvoice)
                <tr><th>Paid</th><td>{{ number_format($document->amount_paid, 2) }}</td></tr>
                <tr><th>Balance</th><td>{{ number_format($document->balance, 2) }}</td></tr>
            @endif
            @unless($isPartPaymentInvoice)<tr class="grand"><th>Grand Total</th><td>{{ number_format($document->total, 2) }}</td></tr>@endunless
        </table>
    </div>
</div>
<div class="footer">Thank you for choosing {{ $companyName }}.</div>
</body>
</html>
