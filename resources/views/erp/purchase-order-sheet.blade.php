@php
    $pdf = $pdf ?? false;
    $documentColors = $settings?->documentColors() ?? [
        'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
        'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
        'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
    ];
    $primaryColor = $documentColors['primary'];
    $secondaryColor = $documentColors['secondary'];
    $accentColor = $documentColors['accent'];
    $companyName = $settings?->company_name ?: 'Purchase Order';
    $logoPath = $pdf ? $settings?->logoFilePath() : $settings?->logoUrl();
    $initials = \Illuminate\Support\Str::of($companyName)->explode(' ')->filter()->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') ?: 'BA';
    $currency = $settings?->currency_code ?: 'KES';
    $formatMoney = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
@endphp
<style>
    .po-sheet{border:1px solid #d7dee7;padding:28px;color:#17202a;background:#fff}
    .po-sheet .po-top{height:7px;background:linear-gradient(90deg,{{ $primaryColor }} 0 78%,{{ $secondaryColor }} 78% 100%);margin:-28px -28px 24px}
    .po-sheet .po-header{width:100%;margin-bottom:26px}
    .po-sheet .po-header td{vertical-align:top}
    .po-sheet .po-brand{width:68%}
    .po-sheet .po-brand-table{width:100%}
    .po-sheet .po-brand-table td{vertical-align:top}
    .po-sheet .po-logo-cell{width:66px}
    .po-sheet .po-logo-frame{width:58px;height:50px;text-align:center;vertical-align:middle;background:#fff}
    .po-sheet .po-logo{max-width:58px;max-height:50px;object-fit:contain}
    .po-sheet .po-logo-fallback{display:inline-block;width:42px;height:42px;border-radius:50%;background:{{ $primaryColor }};color:#fff;text-align:center;line-height:42px;font-weight:bold}
    .po-sheet .po-meta{text-align:right}
    .po-sheet h1{color:#17202a;font-size:22px;margin:0 0 8px}
    .po-sheet .po-type{color:{{ $primaryColor }}}
    .po-sheet h2{font-size:14px;margin:0 0 8px}
    .po-sheet .po-muted{color:#687385}
    .po-sheet .po-details{width:100%;margin:20px 0}
    .po-sheet .po-details td{width:100%;vertical-align:top;padding:12px;background:{{ $accentColor }};border:1px solid #e0e7e3}
    .po-sheet .po-items{width:100%;border-collapse:collapse;margin-top:18px}
    .po-sheet .po-items th{text-align:left;background:{{ $primaryColor }};color:white;padding:10px}
    .po-sheet .po-items td{padding:10px;border-bottom:1px solid #e0e5eb}
    .po-sheet .po-right{text-align:right}
    .po-sheet .po-totals{width:100%;max-width:300px;margin:18px 0 0 auto;border-collapse:collapse}
    .po-sheet .po-totals td{padding:5px 0;text-align:right}
    .po-sheet .po-totals tr:last-child td{border-top:1px solid #d7dee7;padding-top:9px;font-size:15px;font-weight:bold}
    .po-sheet .po-notes{margin-top:24px;white-space:pre-line}
    @media print{.po-sheet{border:0}}
</style>
<article class="{{ $pdf ? 'po-document' : 'card shadow-sm mx-auto' }}" style="{{ $pdf ? '' : 'max-width: 850px;' }}">
    <div class="po-sheet {{ $pdf ? '' : 'card-body p-4 p-md-5' }}">
        @if($pdf)<div class="po-top"></div>@endif
        <table class="po-header">
            <tr>
                <td class="po-brand">
                    <table class="po-brand-table"><tr>
                        <td class="po-logo-cell"><div class="po-logo-frame">
                            @if($logoPath)<img class="po-logo" src="{{ $logoPath }}" alt="{{ $companyName }} logo">
                            @else<span class="po-logo-fallback">{{ strtoupper($initials) }}</span>@endif
                        </div></td>
                        <td>
                            <h1>{{ $companyName }}</h1>
                            @if($settings?->phone || $settings?->email)<div class="po-muted">{{ $settings?->phone }}@if($settings?->phone && $settings?->email) · @endif{{ $settings?->email }}</div>@endif
                            @if($settings?->address)<div>{{ $settings->address }}</div>@endif
                            @if($settings?->location)<div>{{ $settings->location }}</div>@endif
                        </td>
                    </tr></table>
                </td>
                <td class="po-meta">
                    <h2 class="po-type">PURCHASE ORDER</h2>
                    <strong>{{ $purchaseOrder->po_number }}</strong><br>
                    <span class="po-muted">{{ $purchaseOrder->order_date?->format('d M Y') ?: 'Date not set' }}</span><br>
                    <span>{{ $purchaseOrder->status }}</span>
                </td>
            </tr>
        </table>
        <table class="po-details">
            <tr>
                <td><strong>Supplier</strong><br>{{ $purchaseOrder->supplier?->name }}<br><span class="po-muted">{{ $purchaseOrder->supplier?->address }}<br>{{ $purchaseOrder->supplier?->email }} · {{ $purchaseOrder->supplier?->phone }}</span></td>
            </tr>
        </table>
        <table class="po-items">
            <thead><tr><th>Description</th><th class="po-right">Quantity</th><th class="po-right">Unit price</th><th class="po-right">Tax</th><th class="po-right">Total</th></tr></thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                    <tr><td>{{ $item->description }}</td><td class="po-right">{{ number_format((float) $item->quantity, 3) }}</td><td class="po-right">{{ $formatMoney($item->unit_price) }}</td><td class="po-right">{{ number_format((float) $item->tax_rate, 2) }}% · {{ $formatMoney($item->tax_amount) }}</td><td class="po-right">{{ $formatMoney($item->line_total) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <table class="po-totals">
            <tr><td>Subtotal</td><td>{{ $formatMoney((float) $purchaseOrder->amount - (float) $purchaseOrder->tax_amount) }}</td></tr>
            <tr><td>Tax</td><td>{{ $formatMoney($purchaseOrder->tax_amount) }}</td></tr>
            <tr><td>Total</td><td>{{ $formatMoney($purchaseOrder->amount) }}</td></tr>
        </table>
        @if($purchaseOrder->notes)<div class="po-notes"><strong>Notes</strong><br>{{ $purchaseOrder->notes }}</div>@endif
    </div>
</article>
