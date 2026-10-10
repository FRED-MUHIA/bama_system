<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $order->kitchen_status === 'Served' ? 'Order receipt' : 'Kitchen order' }} {{ $order->posOrder->order_number }}</title>
    <style>
        body{margin:0;padding:24px;background:#fffdfa;color:#101010;font:16px/1.5 system-ui,sans-serif}
        main{max-width:420px;margin:20px auto;padding:24px;background:white;border:1px solid #dedbd5;border-radius:14px;font:14px/1.5 ui-monospace,Consolas,monospace}
        h1{margin:0 0 12px;font-size:1.8rem}h2{font-size:1.15rem}p{margin:8px 0}
        .status{color:#007d3d;font-weight:700}table{width:100%;border-collapse:collapse;margin:20px 0}th,td{padding:10px 4px;text-align:left;border-bottom:1px solid #eee}th:last-child,td:last-child{text-align:right}
        .totals{display:flex;justify-content:space-between;gap:16px}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}button,a{font:inherit}button{background:#00a651;color:white;border:0;border-radius:8px;padding:10px 18px;cursor:pointer}a{color:#007d3d}
        .payment-record{border-top:1px dashed #aaa;padding:10px 0;break-inside:avoid}
        @media print{@page{size:80mm auto;margin:3mm}body{padding:0;background:white}main{border:0;margin:0;padding:0;width:74mm;max-width:100%;font-size:12px}.actions{display:none}}
    </style>
</head>
<body>
<main>
    @php
        $invoice = $order->posOrder->invoice;
        $paid = (float) ($invoice?->amount_paid ?? $order->posOrder->amount_paid ?? 0);
        $balance = max((float) $order->posOrder->total - $paid, 0);
    @endphp
    <h2>{{ $order->business?->name ?? config('app.name') }}</h2>
    <h1>{{ $order->kitchen_status === 'Served' ? 'Order receipt' : 'Kitchen order' }}</h1>
    <p><strong>Order {{ $order->posOrder->order_number }}</strong></p>
    <p>Date: {{ $order->created_at?->format('d/m/Y H:i') }}</p>
    <p>Serving staff: {{ $order->waiter?->name ?? 'Not assigned' }}</p>
    <p class="status">{{ $order->kitchen_status }}</p>
    <p>{{ $order->order_type }} @if($order->restaurantTable) · Table {{ $order->restaurantTable->table_number }} @endif</p>
    @if($order->room)<p>Room: {{ $order->room->room_number }}</p>@endif
    @if($order->kitchen_status === 'Served')
        <p>Served {{ $order->kitchen_served_at?->format('d M Y H:i') }}</p>
    @elseif($order->kitchen_status === 'Cancelled')
        <p>This order has been cancelled.</p>
    @else
        <p>Your order is with the kitchen. This page updates automatically. Your receipt will appear here once your order is served.</p>
    @endif
    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead>
        <tbody>
        @foreach($order->posOrder->items as $item)
            <tr><td>{{ $item->title }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->line_total, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <p class="totals"><span>Subtotal</span><span>{{ number_format($order->posOrder->subtotal, 2) }}</span></p>
    <p class="totals"><span>Discount</span><span>{{ number_format($order->posOrder->discount_total, 2) }}</span></p>
    <p class="totals"><span>Tax</span><span>{{ number_format($order->posOrder->tax_total, 2) }}</span></p>
    <p class="totals"><strong>Total</strong><strong>{{ number_format($order->posOrder->total, 2) }}</strong></p>
    <p class="totals"><strong>Paid</strong><strong>{{ number_format($paid, 2) }}</strong></p>
    <p class="totals"><strong>Balance</strong><strong>{{ number_format($balance, 2) }}</strong></p>
    <p>Payment status: {{ $invoice ? ($balance <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Open')) : $order->billing_status }}</p>
    <h2>Payment receipts</h2>
    @forelse($invoice?->receipts ?? collect() as $receipt)
        <div class="payment-record">
            <strong>{{ $receipt->receipt_number }}</strong>
            <p>{{ $receipt->payment_date?->format('d/m/Y') }} · {{ $receipt->payment_method ?: 'Payment' }}</p>
            @if($receipt->payment?->reference)<p>Reference: {{ $receipt->payment->reference }}</p>@endif
            <p class="totals"><span>Payment</span><span>{{ number_format($receipt->amount_paid, 2) }}</span></p>
            <p class="totals"><span>Balance after payment</span><span>{{ number_format($receipt->balance_remaining, 2) }}</span></p>
        </div>
    @empty
        <p>No payments recorded yet.</p>
    @endforelse
    @if($order->notes)<p>Special request: {{ $order->notes }}</p>@endif
    <div class="actions">
        @if($order->kitchen_status === 'Served')<button type="button" onclick="window.print()">Print receipt</button>@endif
        <a href="{{ route('public.hospitality.menu') }}">Back to menu</a>
    </div>
</main>
@if(! in_array($order->kitchen_status, ['Served', 'Cancelled'], true))
<script>setTimeout(() => window.location.reload(), 15000);</script>
@endif
</body>
</html>
