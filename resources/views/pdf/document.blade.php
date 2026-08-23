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
        @page { margin: 22px 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color:#101828; font-size:9.5px; line-height:1.35; }
        .sheet { border:1px solid #d9e1ec; border-radius:7px; overflow:hidden; }
        .top-bar { height:7px; background:{{ $primaryColor }}; }
        .inner { padding:22px; }
        table { width:100%; border-collapse:collapse; }
        .head { margin-bottom:18px; }
        .brand-cell { width:66%; vertical-align:top; }
        .meta-cell { width:34%; vertical-align:top; text-align:right; }
        .brand-table td { vertical-align:top; }
        .logo-cell { width:48px; }
        .logo { width:42px; max-height:42px; object-fit:contain; }
        .logo-fallback { width:42px; height:42px; border-radius:21px; background:{{ $primaryColor }}; color:#fff; text-align:center; line-height:42px; font-weight:bold; }
        .company h2 { margin:0 0 2px; font-size:15px; color:#111827; }
        .company div { color:#344054; }
        .doc-type { color:{{ $primaryColor }}; font-size:8px; font-weight:bold; letter-spacing:1.5px; text-transform:uppercase; }
        .doc-number { font-size:10px; font-weight:bold; color:#111827; margin-bottom:3px; }
        .box-table { margin-bottom:16px; }
        .box { border:1px solid #dbe4ef; background:#f9fbfd; border-radius:7px; padding:12px; min-height:118px; vertical-align:top; }
        .gap { width:14px; }
        .label { font-size:7px; font-weight:bold; letter-spacing:1.4px; text-transform:uppercase; color:#475467; margin-bottom:7px; }
        .muted { color:#475467; }
        .summary td { border-bottom:1px solid #e7edf4; padding:4px 0; }
        .summary td:last-child { text-align:right; font-weight:bold; }
        .balance { margin-top:7px; background:#101828; color:#fff; border-radius:5px; padding:7px; text-align:center; font-weight:bold; }
        .verify { margin-top:10px; padding-top:10px; border-top:1px solid #e7edf4; }
        .verify img { width:62px; height:62px; border:1px solid #d0d7e2; padding:3px; vertical-align:middle; margin-right:7px; }
        .items { margin-bottom:15px; }
        .items th { background:{{ $primaryColor }}; color:#fff; font-size:7px; text-transform:uppercase; letter-spacing:1.2px; padding:7px; text-align:left; }
        .items th:last-child, .items td:last-child { text-align:right; }
        .items td { border-bottom:1px solid #edf1f6; padding:7px; vertical-align:top; }
        .lower-cell { vertical-align:top; }
        .methods { width:58%; padding-right:14px; }
        .totals-cell { width:42%; }
        .method { border:1px solid #dbe4ef; border-radius:5px; padding:7px; margin-bottom:6px; }
        .note { border:1px solid #dbe4ef; border-radius:7px; background:#f9fbfd; padding:10px; margin-top:10px; white-space:pre-line; }
        .totals { border:1px solid #dbe4ef; border-radius:7px; background:#f9fbfd; }
        .totals th, .totals td { padding:5px 8px; border-bottom:1px solid #e7edf4; }
        .totals th { text-align:left; font-weight:normal; }
        .totals td { text-align:right; }
        .totals tr:last-child th, .totals tr:last-child td { border-bottom:0; font-weight:bold; color:#111827; }
        .signature { margin-top:14px; color:#475467; }
        .signature-line { width:140px; border-top:1px solid #98a2b3; margin-bottom:5px; }
        .sig-img { max-height:45px; max-width:120px; margin-right:8px; vertical-align:bottom; }
        .stamp-img { max-height:58px; max-width:82px; vertical-align:bottom; }
        .footer { position:fixed; bottom:-12px; left:24px; right:24px; text-align:center; color:#667085; font-size:8px; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="top-bar"></div>
    <div class="inner">
        <table class="head">
            <tr>
                <td class="brand-cell">
                    <table class="brand-table">
                        <tr>
                            <td class="logo-cell">
                                @if($logoPath)
                                    <img class="logo" src="{{ $logoPath }}">
                                @else
                                    <div class="logo-fallback">BA</div>
                                @endif
                            </td>
                            <td class="company">
                                <h2>{{ $companyName }}</h2>
                                <div>{{ $companySubtitle }}</div>
                                @if($issuerProfile['phone'] ?? $settings?->phone)<div>{{ $issuerProfile['phone'] ?? $settings?->phone }}</div>@endif
                                @if($issuerProfile['email'] ?? $settings?->email)<div>{{ $issuerProfile['email'] ?? $settings?->email }}</div>@endif
                                @if($issuerProfile['address'] ?? $settings?->address)<div>{{ $issuerProfile['address'] ?? $settings?->address }}</div>@endif
                                @if($settings?->location)<div>{{ $settings->location }}</div>@endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="meta-cell">
                    <div class="doc-type">{{ $title }}</div>
                    <div class="doc-number">{{ $number }}</div>
                    <div>Date: {{ $date?->format('M d, Y') ?: '-' }}</div>
                    <div>{{ $secondaryDateLabel }}: {{ $secondaryDate }}</div>
                </td>
            </tr>
        </table>

        <table class="box-table">
            <tr>
                <td class="box" style="width:60%;">
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
                <td class="box" style="width:40%;">
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
                            <img src="{{ $qrCode }}"> Verify invoice origin
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
