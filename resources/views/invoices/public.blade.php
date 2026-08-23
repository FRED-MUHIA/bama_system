<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $issuerProfile = $invoice->issuer_profile ?? [];
        $recipientProfile = $invoice->recipient_profile ?? [];
        $industryContext = $invoice->industry_context ?? [];
        $companyName = $issuerProfile['name'] ?? $settings?->company_name ?? 'BAMA';
        $companySubtitle = $issuerProfile['subtitle'] ?? 'Business Services';
        $documentColors = $settings?->documentColors() ?? [
            'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
            'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
            'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
        ];
        $primaryColor = $documentColors['primary'];
        $secondaryColor = $documentColors['secondary'];
        $accentColor = $documentColors['accent'];
    @endphp
    <title>{{ $invoice->invoice_number }} - {{ $companyName }}</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; background:#eef2f7; color:#111827; font-family:Arial, Helvetica, sans-serif; font-size:14px; }
        .toolbar { position:sticky; top:0; z-index:5; background:#fff; border-bottom:1px solid #e5e7eb; padding:12px 18px; display:flex; justify-content:flex-end; }
        .btn { display:inline-block; background:{{ $primaryColor }}; color:#fff; text-decoration:none; border-radius:7px; padding:10px 18px; font-weight:700; }
        .btn:hover { opacity:.88; }
        .sheet { position:relative; max-width:860px; min-height:1060px; margin:28px auto; padding:54px 64px 76px; background:#fff; overflow:hidden; box-shadow:0 18px 45px rgba(15,23,42,.12); }
        .accent-top { position:absolute; top:0; left:0; width:360px; height:28px; background:{{ $primaryColor }}; border-bottom-right-radius:18px; }
        .accent-top-dark { position:absolute; top:0; left:330px; width:115px; height:28px; background:{{ $secondaryColor }}; border-bottom-left-radius:18px; border-bottom-right-radius:18px; }
        .accent-bottom { position:absolute; right:0; bottom:0; width:360px; height:32px; background:{{ $primaryColor }}; border-top-left-radius:22px; }
        .accent-bottom-dark { position:absolute; left:0; bottom:0; width:150px; height:32px; background:{{ $secondaryColor }}; border-top-right-radius:22px; }
        .header { display:flex; justify-content:space-between; gap:24px; margin-bottom:34px; }
        .company h2 { margin:0 0 2px; color:{{ $secondaryColor }}; font-size:25px; line-height:1; }
        .subtitle { color:{{ $primaryColor }}; font-weight:700; margin-bottom:8px; }
        .muted { color:#4b5563; }
        .logo { max-width:95px; max-height:80px; }
        .logo-fallback { width:76px; height:76px; border-radius:50%; background:{{ $secondaryColor }}; color:#fff; display:grid; place-items:center; text-align:center; font-size:12px; font-weight:800; line-height:1.1; }
        .verify-box { margin-top:12px; text-align:right; color:#4b5563; font-size:12px; }
        .verify-box img { width:112px; height:112px; border:1px solid #e5e7eb; padding:6px; background:#fff; }
        .doc-title { text-align:center; margin-bottom:34px; }
        .doc-title h1 { font-size:21px; letter-spacing:2px; text-decoration:underline; margin:0; }
        .doc-title div { font-size:12px; }
        .parties { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:28px; }
        .dates { text-align:right; }
        .label { font-weight:800; }
        .profile-box { margin-top:12px; padding-top:10px; border-top:1px solid #e5e7eb; color:#374151; font-size:12px; line-height:1.5; }
        table { width:100%; border-collapse:collapse; }
        .items th { background:{{ $accentColor }}; color:{{ $secondaryColor }}; padding:11px 10px; font-size:12px; text-align:center; }
        .items td { border:1px solid #d1d5db; padding:12px 10px; vertical-align:top; }
        .items .text { text-align:left; }
        .items .number { text-align:center; white-space:nowrap; }
        .lower { display:grid; grid-template-columns:1.25fr .9fr; gap:0; align-items:start; }
        .notes { padding:22px 24px 0 0; }
        .notes h3 { margin:0 0 5px; font-size:14px; }
        .notes p { margin:0 0 12px; color:#374151; }
        .totals th, .totals td { border:1px solid #d1d5db; padding:13px 12px; }
        .totals th { text-align:center; font-weight:500; }
        .totals td { text-align:right; }
        .totals .grand th, .totals .grand td { font-weight:800; }
        .signature { margin-top:48px; }
        .signature-line { width:160px; border-top:1px solid {{ $secondaryColor }}; margin-bottom:6px; }
        .footer { position:absolute; left:64px; right:64px; bottom:42px; text-align:center; color:#6b7280; font-size:12px; }
        @media (max-width: 760px) {
            .sheet { margin:0; min-height:auto; padding:46px 18px 64px; }
            .header, .parties, .lower { grid-template-columns:1fr; display:grid; }
            .dates { text-align:left; }
            .items { min-width:620px; }
            .table-wrap { overflow-x:auto; }
            .footer { position:static; margin-top:28px; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <a class="btn" href="{{ route('public.invoices.download', $invoice->public_token) }}">Download PDF</a>
</div>
<main class="sheet">
    <div class="accent-top"></div><div class="accent-top-dark"></div><div class="accent-bottom"></div><div class="accent-bottom-dark"></div>

    <section class="header">
        <div class="company">
            <h2>{{ $companyName }}</h2>
            <div class="subtitle">{{ $companySubtitle }}</div>
            @if($issuerProfile['address'] ?? $settings?->address)<div class="muted">{{ $issuerProfile['address'] ?? $settings?->address }}</div>@endif
            @if($issuerProfile['phone'] ?? $settings?->phone)<div class="muted">{{ $issuerProfile['phone'] ?? $settings?->phone }}</div>@endif
            @if($issuerProfile['email'] ?? $settings?->email)<div class="muted">{{ $issuerProfile['email'] ?? $settings?->email }}</div>@endif
            @if($issuerProfile['website'] ?? $settings?->website)<div class="muted">{{ $issuerProfile['website'] ?? $settings?->website }}</div>@endif
        </div>
        <div>
            @if($settings?->logoUrl())
                <img class="logo" src="{{ $settings->logoUrl() }}" alt="Logo">
            @else
                <div class="logo-fallback">LOGO<br>HERE</div>
            @endif
            <div class="verify-box">
                <img src="{{ $qrCode }}" alt="Invoice verification QR code"><br>
                Scan to verify invoice
            </div>
        </div>
    </section>

    <section class="doc-title">
        <h1>{{ $invoice->isAllocationInvoice() ? 'ALLOCATION INVOICE' : 'INVOICE' }}</h1>
        <div>Invoice Number: {{ $invoice->invoice_number }}</div>
        @if($invoice->industry_reference)<div>Reference Code: {{ $invoice->industry_reference }}</div>@endif
        @if($invoice->isAllocationInvoice() && $invoice->parentInvoice)<div>Parent Invoice: {{ $invoice->parentInvoice->invoice_number }}</div>@endif
        <div class="muted">Verification link: {{ $verificationUrl }}</div>
    </section>

    <section class="parties">
        <div>
            <div class="label">Bill to:</div>
            <strong>{{ $recipientProfile['name'] ?? $invoice->client->name }}</strong><br>
            {{ $invoice->client->company_name }}<br>
            {{ $recipientProfile['address'] ?? $invoice->client->address }}<br>
            {{ $recipientProfile['phone'] ?? $invoice->client->phone }} {{ $recipientProfile['email'] ?? $invoice->client->email }}
            @if($invoice->industry_module === 'real_estate')
                <div class="profile-box">
                    @if(!empty($recipientProfile['tenant_number']))Tenant: {{ $recipientProfile['tenant_number'] }}<br>@endif
                    @if(!empty($recipientProfile['id_number']))ID Number: {{ $recipientProfile['id_number'] }}<br>@endif
                    @if(!empty($recipientProfile['passport_number']))Passport: {{ $recipientProfile['passport_number'] }}<br>@endif
                    @if(!empty($industryContext['property_name']))Property: {{ $industryContext['property_name'] }} @if(!empty($industryContext['property_code']))({{ $industryContext['property_code'] }})@endif<br>@endif
                    @if(!empty($industryContext['unit_number']))Unit: {{ $industryContext['unit_number'] }} @if(!empty($industryContext['unit_type']))- {{ $industryContext['unit_type'] }}@endif<br>@endif
                    @if(!empty($industryContext['lease_number']))Lease: {{ $industryContext['lease_number'] }}<br>@endif
                    @if(!empty($industryContext['source_reference']))Source: {{ $industryContext['source_reference'] }}@endif
                </div>
            @endif
            @if($invoice->industry_module === 'printing_branding')
                <div class="profile-box">
                    @if(!empty($industryContext['job_number']))Job: {{ $industryContext['job_number'] }}<br>@endif
                    @if(!empty($industryContext['ticket_number']))Ticket: {{ $industryContext['ticket_number'] }}<br>@endif
                    @if(!empty($industryContext['invoice_type']))Invoice Type: {{ $industryContext['invoice_type'] }}<br>@endif
                    @if(!empty($industryContext['product_name']))Product: {{ $industryContext['product_name'] }}<br>@endif
                    @if(isset($industryContext['quantity']))Quantity: {{ number_format((float) $industryContext['quantity'], 3) }}<br>@endif
                    @if(!empty($industryContext['delivery_date']))Delivery Date: {{ $industryContext['delivery_date'] }}<br>@endif
                    @if(!empty($industryContext['job_status']))Production Status: {{ $industryContext['job_status'] }}@endif
                </div>
            @endif
            @if($invoice->relationLoaded('project') && $invoice->project)<br><br><strong>Project:</strong> {{ $invoice->project->project_name }}@endif
            @if($invoice->relationLoaded('site') && $invoice->site)<br><strong>Site:</strong> {{ $invoice->site->site_name }}@endif
        </div>
        <div class="dates">
            <div class="label">Due Date:</div>
            {{ $invoice->due_date?->format('F j, Y') ?: '-' }}<br><br>
            <div class="label">Invoice Date:</div>
            {{ $invoice->invoice_date?->format('F j, Y') }}
        </div>
    </section>

    <div class="table-wrap">
        <table class="items">
            <thead><tr><th>Item Description</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="text"><strong>{{ $item->title ?: $item->description }}</strong>@if($item->title)<br>{{ $item->description }}@endif</td>
                        <td class="number">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="number">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                        <td class="number">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <section class="lower">
        <div class="notes">
            @if($paymentMethods->count())
                <h3>Payment Methods</h3>
                @foreach($paymentMethods as $method)
                    <p><strong>{{ $method->name }}:</strong><br>{{ $method->details }}</p>
                @endforeach
            @endif
            @if($invoice->terms)
                <h3>Terms and Conditions :</h3>
                <p>{{ $invoice->terms }}</p>
            @endif
            <div class="signature">
                <div class="signature-line"></div>
                <strong>{{ $companyName }}</strong><br>
                <span class="muted">Authorized Representative</span>
            </div>
        </div>
        <div>
            <table class="totals">
                @if($invoice->isAllocationInvoice())
                    <tr class="grand"><th>Allocated Amount</th><td>{{ number_format($invoice->part_payment_amount, 2) }}</td></tr>
                    @if($invoice->parentInvoice)<tr><th>Source Invoice Total</th><td>{{ number_format($invoice->parentInvoice->total, 2) }}</td></tr>@endif
                @else
                    <tr><th>Subtotal</th><td>{{ number_format($invoice->subtotal, 2) }}</td></tr>
                    <tr><th>Tax</th><td>{{ $invoice->tax_total > 0 ? number_format($invoice->tax_total, 2) : '-' }}</td></tr>
                    <tr><th>Discount</th><td>{{ $invoice->discount_total > 0 ? number_format($invoice->discount_total, 2) : '-' }}</td></tr>
                    <tr><th>Paid</th><td>{{ number_format($invoice->amount_paid, 2) }}</td></tr>
                    <tr><th>Balance</th><td>{{ number_format($invoice->balance, 2) }}</td></tr>
                    <tr class="grand"><th>Grand Total</th><td>{{ number_format($invoice->total, 2) }}</td></tr>
                @endif
            </table>
        </div>
    </section>

    <div class="footer">Thank you for choosing {{ $companyName }}.</div>
</main>
</body>
</html>
