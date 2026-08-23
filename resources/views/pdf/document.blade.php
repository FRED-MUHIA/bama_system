@php
    $settings ??= null;
    $paymentMethods ??= collect();
    $signatory ??= null;
    $qrCode ??= null;
    $verificationUrl ??= null;

    $isReceipt = $type === 'Receipt';
    $sourceInvoice = $isReceipt ? $document->invoice : null;
    $client = $isReceipt ? $sourceInvoice?->client : $document->client;
    $issuerProfile = ! $isReceipt && $type === 'Invoice' ? ($document->issuer_profile ?? []) : [];
    $recipientProfile = ! $isReceipt && $type === 'Invoice' ? ($document->recipient_profile ?? []) : [];
    $industryContext = ! $isReceipt && $type === 'Invoice' ? ($document->industry_context ?? []) : [];
    $documentColors = $settings?->documentColors() ?? [
        'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
        'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
        'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
    ];
    $primaryColor = $documentColors['primary'];
    $secondaryColor = $documentColors['secondary'];
    $accentColor = $documentColors['accent'];
    $companyName = $issuerProfile['name'] ?? $settings?->company_name ?? 'BAMA';
    $companySubtitle = $issuerProfile['subtitle'] ?? 'Business Services';
    $currency = $settings?->currency_code ?: 'KES';
    $money = fn ($value) => $currency.' '.number_format((float) $value, 2);
    $number = match ($type) {
        'Quotation' => $document->quotation_number,
        'Receipt' => $document->receipt_number,
        default => $document->invoice_number,
    };
    $date = match ($type) {
        'Quotation' => $document->quotation_date,
        'Receipt' => $document->payment_date,
        default => $document->invoice_date,
    };
    $secondaryDateLabel = match ($type) {
        'Quotation' => 'Valid until',
        'Receipt' => 'Invoice',
        default => 'Due',
    };
    $secondaryDate = match ($type) {
        'Quotation' => $document->valid_until?->format('M d, Y') ?: '-',
        'Receipt' => $sourceInvoice?->invoice_number ?: '-',
        default => $document->due_date?->format('M d, Y') ?: '-',
    };
    $status = match ($type) {
        'Receipt' => $document->status ?: 'Paid',
        'Quotation' => $document->status,
        default => $document->payment_status,
    };
    $logoPath = $settings?->logoFilePath();
    $initials = \Illuminate\Support\Str::of($companyName)->explode(' ')->filter()->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') ?: 'BA';
    $signaturePath = $signatory?->signatureFilePath();
    $stampPath = $signatory?->stampFilePath();
    $isAllocationInvoice = $type === 'Invoice' && $document->isAllocationInvoice();
    $title = $isAllocationInvoice ? 'ALLOCATION INVOICE' : strtoupper($type);
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size:A4 portrait; margin: 20px 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color:#020617; font-size:9px; line-height:1.35; }
        .sheet { border:1px solid #e3e8ef; border-radius:12px; overflow:hidden; }
        .top-bar { width:100%; height:8px; border-collapse:collapse; }
        .top-bar td { padding:0; }
        .top-primary { width:78%; background:{{ $primaryColor }}; }
        .top-secondary { width:22%; background:{{ $secondaryColor }}; }
        .top-accent { width:0; background:{{ $accentColor }}; }
        .inner { padding:22px 24px 24px; }
        table { width:100%; border-collapse:collapse; }
        .letterhead { margin-bottom:24px; }
        .brand-cell { width:68%; vertical-align:top; }
        .meta-cell { width:32%; vertical-align:top; text-align:right; }
        .brand-table td { vertical-align:top; }
        .logo-cell { width:54px; }
        .logo-frame { width:42px; height:42px; background:#fff; text-align:center; vertical-align:middle; }
        .logo { max-width:42px; max-height:42px; object-fit:contain; }
        .logo-fallback { width:42px; height:42px; border-radius:21px; background:{{ $primaryColor }}; color:#fff; text-align:center; line-height:42px; font-size:11px; font-weight:bold; }
        .company h2 { margin:0 0 4px; font-size:15px; color:#020617; line-height:1.1; }
        .company-subtitle { display:none; }
        .company-detail { color:#020617; font-size:8px; line-height:1.45; }
        .doc-type { color:{{ $primaryColor }}; font-size:7px; font-weight:bold; letter-spacing:4px; text-transform:uppercase; margin-bottom:4px; }
        .doc-number { font-size:13px; font-weight:bold; color:#020617; margin-bottom:5px; }
        .meta-line { color:#020617; font-size:8px; line-height:1.45; }
        .meta-line strong { color:#020617; font-weight:normal; }
        .box-table { margin-bottom:20px; }
        .box { border:1px solid #dbe3ee; background:{{ $accentColor }}; border-radius:9px; padding:12px; vertical-align:top; }
        .gap { width:16px; }
        .label { font-size:7px; font-weight:bold; letter-spacing:3px; text-transform:uppercase; color:#020617; margin-bottom:9px; }
        .muted { color:#020617; }
        .summary td { border-bottom:1px solid #e4eaf2; padding:4px 0; color:#020617; }
        .summary td:last-child { text-align:right; font-weight:bold; }
        .balance { margin-top:7px; background:{{ $secondaryColor }}; color:#fff; border-radius:8px; padding:7px; text-align:center; font-weight:bold; }
        .verify { margin-top:12px; padding-top:10px; border-top:1px solid #e4eaf2; }
        .verify img { width:54px; height:54px; border:1px solid #d0d7e2; padding:3px; vertical-align:middle; margin-right:8px; }
        .verify-text { word-break:break-word; }
        .items { margin-bottom:22px; }
        .items th { background:{{ $primaryColor }}; color:#fff; font-size:7px; text-transform:uppercase; letter-spacing:2px; padding:8px; text-align:left; }
        .items th:last-child, .items td:last-child { text-align:right; }
        .items td { border-bottom:0; padding:10px 8px; vertical-align:top; color:#020617; }
        .lower-cell { vertical-align:top; }
        .methods { width:61%; padding-right:16px; }
        .totals-cell { width:39%; }
        .method { border:1px solid #dbe3ee; border-radius:8px; padding:7px 10px; margin-bottom:6px; color:#020617; }
        .note { border:1px solid #dbe3ee; border-radius:9px; background:{{ $accentColor }}; padding:10px; margin-top:10px; white-space:pre-line; color:#020617; }
        .totals { border:1px solid #dbe3ee; border-radius:9px; background:{{ $accentColor }}; }
        .totals th, .totals td { padding:5px 8px; border-bottom:0; color:#020617; }
        .totals th { text-align:left; font-weight:normal; }
        .totals td { text-align:right; }
        .totals tr:last-child th, .totals tr:last-child td { border-top:1px solid #e4eaf2; padding-top:8px; font-weight:bold; color:#020617; }
        .signature { margin-top:14px; color:#020617; }
        .signature-line { width:140px; border-top:1px solid #98a2b3; margin-bottom:5px; }
        .sig-img { max-height:45px; max-width:120px; margin-right:8px; vertical-align:bottom; }
        .stamp-img { max-height:58px; max-width:82px; vertical-align:bottom; }
        .footer { position:fixed; bottom:-12px; left:24px; right:24px; text-align:center; color:#020617; font-size:8px; }
    </style>
</head>
<body>
<div class="sheet">
    <table class="top-bar"><tr><td class="top-primary"></td><td class="top-secondary"></td><td class="top-accent"></td></tr></table>
    <div class="inner">
        <table class="letterhead">
            <tr>
                <td class="brand-cell">
                    <table class="brand-table">
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
                                <h2>{{ $companyName }}</h2>
                                <div class="company-subtitle">{{ $companySubtitle }}</div>
                                @if(($issuerProfile['phone'] ?? $settings?->phone) || ($issuerProfile['email'] ?? $settings?->email))
                                    <div class="company-detail">
                                        @if($issuerProfile['phone'] ?? $settings?->phone){{ $issuerProfile['phone'] ?? $settings?->phone }}@endif
                                        @if(($issuerProfile['phone'] ?? $settings?->phone) && ($issuerProfile['email'] ?? $settings?->email)) | @endif
                                        @if($issuerProfile['email'] ?? $settings?->email){{ $issuerProfile['email'] ?? $settings?->email }}@endif
                                    </div>
                                @endif
                                @if($issuerProfile['address'] ?? $settings?->address)<div class="company-detail">{{ $issuerProfile['address'] ?? $settings?->address }}</div>@endif
                                @if($settings?->location)<div class="company-detail">{{ $settings->location }}</div>@endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="meta-cell">
                    <div class="doc-type">{{ $title }}</div>
                    <div class="doc-number">{{ $number }}</div>
                    <div class="meta-line"><strong>Date:</strong> {{ $date?->format('M d, Y') ?: '-' }}</div>
                    <div class="meta-line"><strong>{{ $secondaryDateLabel }}:</strong> {{ $secondaryDate }}</div>
                </td>
            </tr>
        </table>

        <table class="box-table">
            <tr>
                <td class="box" style="width:61%;">
                    <div class="label">{{ $isReceipt ? 'Received from' : 'Bill to' }}</div>
                    <strong>{{ $recipientProfile['name'] ?? $client?->name }}</strong><br>
                    @if($client?->company_name){{ $client->company_name }}<br>@endif
                    @if($recipientProfile['phone'] ?? $client?->phone){{ $recipientProfile['phone'] ?? $client?->phone }}<br>@endif
                    @if($recipientProfile['email'] ?? $client?->email){{ $recipientProfile['email'] ?? $client?->email }}<br>@endif
                    @if($recipientProfile['address'] ?? $client?->address){{ $recipientProfile['address'] ?? $client?->address }}<br>@endif
                    @if(! $isReceipt && $document->relationLoaded('project') && $document->project)<br>Project: {{ $document->project->project_name }}@endif
                    @if(! $isReceipt && $document->relationLoaded('site') && $document->site)<br>Site: {{ $document->site->site_name }}@endif
                    @if(!empty($industryContext['job_number']))<br>Job: {{ $industryContext['job_number'] }}@endif
                    @if(!empty($industryContext['property_name']))<br>Property: {{ $industryContext['property_name'] }}@endif
                </td>
                <td class="gap"></td>
                <td class="box" style="width:39%;">
                    <table class="summary">
                        <tr><td>Status</td><td>{{ $status ?: '-' }}</td></tr>
                        @if($isReceipt)
                            <tr><td>Invoice total</td><td>{{ $money($sourceInvoice?->total) }}</td></tr>
                            <tr><td>Paid</td><td>{{ $money($document->amount_paid) }}</td></tr>
                            <tr><td>Balance</td><td>{{ $money($document->balance_remaining) }}</td></tr>
                        @elseif($isAllocationInvoice)
                            <tr><td>Allocated</td><td>{{ $money($document->part_payment_amount) }}</td></tr>
                            @if($document->parentInvoice)<tr><td>Source total</td><td>{{ $money($document->parentInvoice->total) }}</td></tr>@endif
                        @else
                            <tr><td>Total</td><td>{{ $money($document->total) }}</td></tr>
                            @if($type === 'Invoice')
                                <tr><td>Paid</td><td>{{ $money($document->amount_paid) }}</td></tr>
                                <tr><td>Balance</td><td>{{ $money($document->balance) }}</td></tr>
                            @endif
                        @endif
                    </table>
                    @if($isReceipt)
                        <div class="balance">Receipt: {{ $money($document->amount_paid) }}</div>
                    @elseif($isAllocationInvoice)
                        <div class="balance">Allocation Due: {{ $money($document->part_payment_amount) }}</div>
                    @elseif($type === 'Invoice')
                        <div class="balance">Balance Due: {{ $money($document->balance) }}</div>
                    @endif
                    @if($type === 'Invoice' && $qrCode)
                        <div class="verify">
                            <img src="{{ $qrCode }}"> <span class="verify-text">Scan to verify invoice origin.@if($verificationUrl)<br>{{ $verificationUrl }}@endif</span>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                @if($isReceipt)
                    <tr><th>Description</th><th>Method</th><th>Date</th><th>Total</th></tr>
                @else
                    <tr><th>Item</th><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
                @endif
            </thead>
            <tbody>
                @if($isReceipt)
                    <tr>
                        <td>Payment received for {{ $sourceInvoice?->invoice_number }}</td>
                        <td>{{ $document->payment_method ?: '-' }}</td>
                        <td>{{ $document->payment_date?->format('M d, Y') ?: '-' }}</td>
                        <td>{{ $money($document->amount_paid) }}</td>
                    </tr>
                @else
                    @foreach($document->items as $item)
                        <tr>
                            <td>{{ $item->title ?: '-' }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                            <td>{{ $money($item->unit_price) }}</td>
                            <td>{{ $money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <table>
            <tr>
                <td class="lower-cell methods">
                    @if($paymentMethods->count())
                        <div class="label">Payment methods</div>
                        @foreach($paymentMethods as $method)
                            <div class="method">
                                <strong>{{ $method->name }}</strong><br>
                                @if($method->details){{ $method->details }}@endif
                            </div>
                        @endforeach
                    @endif
                    @if($isReceipt || (! empty($document->terms)))
                        <div class="note">{{ $isReceipt ? 'This receipt confirms payment received against the invoice shown above. Please keep it for your records.' : $document->terms }}</div>
                    @endif
                    <div class="signature">
                        @if($signaturePath || $stampPath)
                            @if($signaturePath)<img class="sig-img" src="{{ $signaturePath }}">@endif
                            @if($stampPath)<img class="stamp-img" src="{{ $stampPath }}">@endif
                        @else
                            <div class="signature-line"></div>
                        @endif
                        <strong>{{ $signatory?->name ?? $companyName }}</strong><br>
                        {{ $signatory?->title ?? 'Authorized Representative' }}
                    </div>
                </td>
                <td class="lower-cell totals-cell">
                    <table class="totals">
                        @if($isReceipt)
                            <tr><th>Invoice Total</th><td>{{ $money($sourceInvoice?->total) }}</td></tr>
                            <tr><th>Amount Paid</th><td>{{ $money($document->amount_paid) }}</td></tr>
                            <tr><th>Balance</th><td>{{ $money($document->balance_remaining) }}</td></tr>
                        @elseif($isAllocationInvoice)
                            <tr><th>Allocated Amount</th><td>{{ $money($document->part_payment_amount) }}</td></tr>
                            @if($document->parentInvoice)<tr><th>Source Total</th><td>{{ $money($document->parentInvoice->total) }}</td></tr>@endif
                        @else
                            <tr><th>Subtotal</th><td>{{ $money($document->subtotal) }}</td></tr>
                            <tr><th>Tax</th><td>{{ $money($document->tax_total) }}</td></tr>
                            <tr><th>Discount</th><td>{{ $money($document->discount_total) }}</td></tr>
                            @if($type === 'Invoice')
                                <tr><th>Paid</th><td>{{ $money($document->amount_paid) }}</td></tr>
                                <tr><th>Balance</th><td>{{ $money($document->balance) }}</td></tr>
                            @endif
                            <tr><th>Total</th><td>{{ $money($document->total) }}</td></tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="footer">Thank you for choosing {{ $companyName }}.</div>
</body>
</html>
