@php
    $type ??= 'Invoice';
    $settings ??= null;
    $paymentMethods ??= collect();
    $signatory ??= null;
    $qrCode ??= null;
    $verificationUrl ??= null;

    $isReceipt = $type === 'Receipt';
    $sourceInvoice = $isReceipt ? $document->invoice : null;
    $client = $isReceipt ? $sourceInvoice?->client : $document->client;
    $items = $isReceipt ? collect() : $document->items;
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
    $logoUrl = $settings?->logoUrl();
    $initials = \Illuminate\Support\Str::of($companyName)->explode(' ')->filter()->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') ?: 'BA';
    $canShowTotals = ! ($type === 'Invoice' && $document->isAllocationInvoice());
@endphp

<style>
    .doc-sheet{--doc-primary:{{ $primaryColor }};--doc-secondary:{{ $secondaryColor }};--doc-accent:{{ $accentColor }};max-width:920px;margin:0 auto 1rem;background:#fff;border:1px solid #dce3ec;border-radius:8px;box-shadow:0 12px 34px rgba(15,23,42,.08);overflow:hidden;color:#101828}
    .doc-sheet *{letter-spacing:0}
    .doc-sheet__bar{height:8px;background:linear-gradient(90deg,var(--doc-primary) 0 68%,var(--doc-secondary) 68% 90%,var(--doc-accent) 90% 100%)}
    .doc-sheet__inner{padding:28px}
    .doc-sheet__head{display:grid;grid-template-columns:1fr auto;gap:22px;align-items:start;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #111827}
    .doc-company{display:flex;gap:16px;align-items:flex-start}
    .doc-logo{width:64px;height:64px;border-radius:4px;border:1px solid #d0d7e2;display:grid;place-items:center;background:#fff;color:#fff;font-weight:900;overflow:hidden;flex:0 0 64px}
    .doc-logo span{display:grid;place-items:center;width:100%;height:100%;background:var(--doc-primary);font-size:1rem}
    .doc-logo img{width:100%;height:100%;object-fit:contain;background:#fff;padding:6px}
    .doc-company h2{font-size:1.18rem;margin:0 0 3px;color:#111827;font-weight:900;text-transform:uppercase;line-height:1.12}
    .doc-company__subtitle{color:var(--doc-primary)!important;font-size:.68rem!important;font-weight:900;text-transform:uppercase;letter-spacing:.08em!important;margin-bottom:7px!important}
    .doc-company p,.doc-meta p,.doc-box p{margin:0;color:#344054;font-size:.76rem;line-height:1.45}
    .doc-meta{text-align:right;min-width:190px}
    .doc-type{display:inline-block;background:var(--doc-primary);color:#fff;font-size:.64rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase;margin-bottom:8px;padding:6px 10px;border-radius:3px}
    .doc-number{font-size:.98rem;font-weight:900;color:#111827;margin-bottom:4px}
    .doc-grid{display:grid;grid-template-columns:1.35fr .85fr;gap:18px;margin-bottom:22px}
    .doc-box{border:1px solid #dbe4ef;background:#f9fbfd;border-radius:8px;padding:16px;min-height:152px}
    .doc-box__label{font-size:.62rem;font-weight:900;letter-spacing:.18em;text-transform:uppercase;color:#475467;margin-bottom:10px}
    .doc-box strong{display:block;color:#111827;margin-bottom:3px}
    .doc-summary-row{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #e7edf4;padding:6px 0;font-size:.78rem}
    .doc-summary-row:last-child{border-bottom:0}
    .doc-balance{margin-top:8px;background:#101828;color:#fff;border-radius:6px;padding:8px 10px;text-align:center;font-weight:900;font-size:.76rem}
    .doc-verify{display:flex;gap:10px;align-items:center;margin-top:14px;padding-top:14px;border-top:1px solid #e7edf4}
    .doc-verify img{width:72px;height:72px;border:1px solid #d0d7e2;background:#fff;padding:4px}
    .doc-verify a{color:var(--doc-primary);font-size:.72rem}
    .doc-table{width:100%;border-collapse:collapse;margin-bottom:20px}
    .doc-table th{background:var(--doc-primary);color:#fff;font-size:.62rem;text-transform:uppercase;letter-spacing:.14em;padding:9px 10px;text-align:left}
    .doc-table th:last-child,.doc-table td:last-child{text-align:right}
    .doc-table td{padding:10px;border-bottom:1px solid #edf1f6;font-size:.78rem;vertical-align:top}
    .doc-table td:nth-child(3),.doc-table td:nth-child(4){white-space:nowrap}
    .doc-lower{display:grid;grid-template-columns:1.35fr .85fr;gap:18px;align-items:start}
    .doc-methods{display:grid;gap:8px}
    .doc-method{border:1px solid #dbe4ef;border-radius:6px;padding:8px 10px;font-size:.74rem;background:#fff}
    .doc-method strong{display:block;margin-bottom:2px}
    .doc-note{border:1px solid #dbe4ef;border-radius:8px;background:#f9fbfd;padding:14px;margin-top:14px;font-size:.74rem;color:#344054;white-space:pre-line}
    .doc-totals{border:1px solid #dbe4ef;border-radius:8px;padding:14px;background:#f9fbfd}
    .doc-totals table{width:100%;border-collapse:collapse}
    .doc-totals th,.doc-totals td{font-size:.76rem;padding:5px 0;border-bottom:1px solid #e7edf4}
    .doc-totals th{text-align:left;font-weight:500}.doc-totals td{text-align:right}.doc-totals tr:last-child th,.doc-totals tr:last-child td{border-bottom:0;font-weight:900;color:#111827}
    .doc-signature{margin-top:16px;font-size:.75rem;color:#475467}
    .doc-signature__assets{display:flex;gap:10px;align-items:end;margin-bottom:8px}
    .doc-signature img{object-fit:contain}.doc-signature__sig{max-width:130px;max-height:52px}.doc-signature__stamp{max-width:92px;max-height:68px}
    .doc-signature__line{width:150px;border-top:1px solid #98a2b3;margin-bottom:6px}
    @media(max-width:760px){.doc-sheet__inner{padding:18px}.doc-sheet__head,.doc-grid,.doc-lower{grid-template-columns:1fr}.doc-meta{text-align:left}.doc-table{min-width:640px}.doc-table-wrap{overflow-x:auto}}
</style>

<article class="doc-sheet">
    <div class="doc-sheet__bar"></div>
    <div class="doc-sheet__inner">
        <header class="doc-sheet__head">
            <div class="doc-company">
                <div class="doc-logo">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $companyName }} logo">
                    @else
                        <span>{{ strtoupper($initials) }}</span>
                    @endif
                </div>
                <div>
                    <h2>{{ $companyName }}</h2>
                    <p class="doc-company__subtitle">{{ $companySubtitle }}</p>
                    @if($issuerProfile['address'] ?? $settings?->address)<p>{{ $issuerProfile['address'] ?? $settings?->address }}</p>@endif
                    @if($settings?->location)<p>{{ $settings->location }}</p>@endif
                    @if(($issuerProfile['phone'] ?? $settings?->phone) || ($issuerProfile['email'] ?? $settings?->email))
                        <p>
                            @if($issuerProfile['phone'] ?? $settings?->phone){{ $issuerProfile['phone'] ?? $settings?->phone }}@endif
                            @if(($issuerProfile['phone'] ?? $settings?->phone) && ($issuerProfile['email'] ?? $settings?->email)) | @endif
                            @if($issuerProfile['email'] ?? $settings?->email){{ $issuerProfile['email'] ?? $settings?->email }}@endif
                        </p>
                    @endif
                </div>
            </div>
            <div class="doc-meta">
                <div class="doc-type">{{ $type }}</div>
                <div class="doc-number">{{ $number }}</div>
                <p>Date: {{ $date?->format('M d, Y') ?: '-' }}</p>
                <p>{{ $secondaryDateLabel }}: {{ $secondaryDate }}</p>
            </div>
        </header>

        <section class="doc-grid">
            <div class="doc-box">
                <div class="doc-box__label">{{ $isReceipt ? 'Received from' : 'Bill to' }}</div>
                <strong>{{ $recipientProfile['name'] ?? $client?->name }}</strong>
                @if($client?->company_name)<p>{{ $client->company_name }}</p>@endif
                @if($recipientProfile['phone'] ?? $client?->phone)<p>{{ $recipientProfile['phone'] ?? $client?->phone }}</p>@endif
                @if($recipientProfile['email'] ?? $client?->email)<p>{{ $recipientProfile['email'] ?? $client?->email }}</p>@endif
                @if($recipientProfile['address'] ?? $client?->address)<p>{{ $recipientProfile['address'] ?? $client?->address }}</p>@endif
                @if(! $isReceipt && $document->relationLoaded('project') && $document->project)<p>Project: {{ $document->project->project_name }}</p>@endif
                @if(! $isReceipt && $document->relationLoaded('site') && $document->site)<p>Site: {{ $document->site->site_name }}</p>@endif
                @if(!empty($industryContext['job_number']))<p>Job: {{ $industryContext['job_number'] }}</p>@endif
                @if(!empty($industryContext['property_name']))<p>Property: {{ $industryContext['property_name'] }}</p>@endif
            </div>
            <div class="doc-box">
                <div class="doc-summary-row"><span>Status</span><strong>{{ $status ?: '-' }}</strong></div>
                @if($isReceipt)
                    <div class="doc-summary-row"><span>Invoice total</span><strong>{{ $money($sourceInvoice?->total) }}</strong></div>
                    <div class="doc-summary-row"><span>Paid</span><strong>{{ $money($document->amount_paid) }}</strong></div>
                    <div class="doc-summary-row"><span>Balance</span><strong>{{ $money($document->balance_remaining) }}</strong></div>
                    <div class="doc-balance">Receipt: {{ $money($document->amount_paid) }}</div>
                @elseif($type === 'Invoice' && $document->isAllocationInvoice())
                    <div class="doc-summary-row"><span>Allocated</span><strong>{{ $money($document->part_payment_amount) }}</strong></div>
                    @if($document->parentInvoice)<div class="doc-summary-row"><span>Source total</span><strong>{{ $money($document->parentInvoice->total) }}</strong></div>@endif
                    <div class="doc-balance">Allocation Due: {{ $money($document->part_payment_amount) }}</div>
                @else
                    <div class="doc-summary-row"><span>Total</span><strong>{{ $money($document->total) }}</strong></div>
                    @if($type === 'Invoice')
                        <div class="doc-summary-row"><span>Paid</span><strong>{{ $money($document->amount_paid) }}</strong></div>
                        <div class="doc-summary-row"><span>Balance</span><strong>{{ $money($document->balance) }}</strong></div>
                        <div class="doc-balance">Balance Due: {{ $money($document->balance) }}</div>
                    @endif
                @endif
                @if($type === 'Invoice' && $qrCode)
                    <div class="doc-verify">
                        <img src="{{ $qrCode }}" alt="Invoice verification QR code">
                        <div>
                            <strong>Verify invoice</strong>
                            @if($verificationUrl)<br><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>@endif
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <div class="doc-table-wrap">
            <table class="doc-table">
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
                        @foreach($items as $item)
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
        </div>

        <section class="doc-lower">
            <div>
                @if($paymentMethods->count())
                    <div class="doc-box__label">Payment methods</div>
                    <div class="doc-methods">
                        @foreach($paymentMethods as $method)
                            <div class="doc-method">
                                <strong>{{ $method->name }}</strong>
                                @if($method->details)<span style="white-space:pre-line">{{ $method->details }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @if($isReceipt || (! empty($document->terms)))
                    <div class="doc-note">{{ $isReceipt ? 'This receipt confirms payment received against the invoice shown above. Please keep it for your records.' : $document->terms }}</div>
                @endif
                <div class="doc-signature">
                    @if($signatory?->signatureUrl() || $signatory?->stampUrl())
                        <div class="doc-signature__assets">
                            @if($signatory?->signatureUrl())<img class="doc-signature__sig" src="{{ $signatory->signatureUrl() }}" alt="Signature">@endif
                            @if($signatory?->stampUrl())<img class="doc-signature__stamp" src="{{ $signatory->stampUrl() }}" alt="Stamp">@endif
                        </div>
                    @else
                        <div class="doc-signature__line"></div>
                    @endif
                    <strong>{{ $signatory?->name ?? $companyName }}</strong><br>
                    {{ $signatory?->title ?? 'Authorized Representative' }}
                </div>
            </div>
            <div class="doc-totals">
                <table>
                    @if($isReceipt)
                        <tr><th>Invoice Total</th><td>{{ $money($sourceInvoice?->total) }}</td></tr>
                        <tr><th>Amount Paid</th><td>{{ $money($document->amount_paid) }}</td></tr>
                        <tr><th>Balance</th><td>{{ $money($document->balance_remaining) }}</td></tr>
                    @elseif($type === 'Invoice' && $document->isAllocationInvoice())
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
            </div>
        </section>
    </div>
</article>
