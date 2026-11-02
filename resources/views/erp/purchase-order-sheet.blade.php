@php
    $pdf = $pdf ?? false;
    $currency = $settings?->currency_code ?: 'KES';
    $formatMoney = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
@endphp
<style>
    .po-sheet{border:1px solid #d7dee7;padding:28px;color:#17202a;background:#fff}
    .po-sheet .po-top{border-top:7px solid #008b50;margin:-28px -28px 24px}
    .po-sheet .po-header{width:100%;margin-bottom:26px}
    .po-sheet .po-header td{vertical-align:top}
    .po-sheet .po-meta{text-align:right}
    .po-sheet h1{color:#008b50;font-size:22px;margin:0 0 8px}
    .po-sheet h2{font-size:14px;margin:0 0 8px}
    .po-sheet .po-muted{color:#687385}
    .po-sheet .po-details{width:100%;margin:20px 0}
    .po-sheet .po-details td{width:50%;vertical-align:top;padding:12px;background:#f5f8f6;border:1px solid #e0e7e3}
    .po-sheet .po-items{width:100%;border-collapse:collapse;margin-top:18px}
    .po-sheet .po-items th{text-align:left;background:#008b50;color:white;padding:10px}
    .po-sheet .po-items td{padding:10px;border-bottom:1px solid #e0e5eb}
    .po-sheet .po-right{text-align:right}
    .po-sheet .po-total{text-align:right;font-size:15px;font-weight:bold;margin-top:18px}
    .po-sheet .po-notes{margin-top:24px;white-space:pre-line}
    @media print{.po-sheet{border:0}}
</style>
<article class="{{ $pdf ? 'po-document' : 'card shadow-sm mx-auto' }}" style="{{ $pdf ? '' : 'max-width: 850px;' }}">
    <div class="po-sheet {{ $pdf ? '' : 'card-body p-4 p-md-5' }}">
        @if($pdf)<div class="po-top"></div>@endif
        <table class="po-header">
            <tr>
                <td>
                    <h1>{{ $settings?->company_name ?: 'Purchase Order' }}</h1>
                    @if($settings?->address)<div>{{ $settings->address }}</div>@endif
                    @if($settings?->phone || $settings?->email)<div class="po-muted">{{ $settings?->phone }} {{ $settings?->email }}</div>@endif
                </td>
                <td class="po-meta">
                    <h2>PURCHASE ORDER</h2>
                    <strong>{{ $purchaseOrder->po_number }}</strong><br>
                    <span class="po-muted">{{ $purchaseOrder->order_date?->format('d M Y') ?: 'Date not set' }}</span><br>
                    <span>{{ $purchaseOrder->status }}</span>
                </td>
            </tr>
        </table>
        <table class="po-details">
            <tr>
                <td><strong>Supplier</strong><br>{{ $purchaseOrder->supplier?->name }}<br><span class="po-muted">{{ $purchaseOrder->supplier?->address }}<br>{{ $purchaseOrder->supplier?->email }} · {{ $purchaseOrder->supplier?->phone }}</span></td>
                <td><strong>Project</strong><br>{{ $purchaseOrder->project?->project_name ?: 'No project' }}<br><span class="po-muted">Department: {{ $purchaseOrder->department?->name ?: 'Automatic' }}<br>Cost center: {{ $purchaseOrder->costCenter?->name ?: 'Automatic' }}</span></td>
            </tr>
        </table>
        <table class="po-items">
            <thead><tr><th>Description</th><th class="po-right">Quantity</th><th class="po-right">Unit price</th><th class="po-right">Line total</th></tr></thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                    <tr><td>{{ $item->description }}</td><td class="po-right">{{ number_format((float) $item->quantity, 3) }}</td><td class="po-right">{{ $formatMoney($item->unit_price) }}</td><td class="po-right">{{ $formatMoney($item->line_total) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="po-total">Total: {{ $formatMoney($purchaseOrder->amount) }}</div>
        @if($purchaseOrder->notes)<div class="po-notes"><strong>Notes</strong><br>{{ $purchaseOrder->notes }}</div>@endif
    </div>
</article>
