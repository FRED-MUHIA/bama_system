@extends('layouts.app')
@section('title', 'Receipt · '.$order->order_number)
@section('content')
<style>
    .receipt-actions{max-width:420px;margin:0 auto 12px;display:flex;justify-content:space-between;align-items:center}
    .thermal-receipt{width:80mm;max-width:100%;margin:0 auto;background:#fff;color:#111;padding:5mm;font:12px/1.35 ui-monospace,SFMono-Regular,Consolas,monospace}
    .thermal-receipt h1{font:700 17px/1.15 ui-monospace,SFMono-Regular,Consolas,monospace;text-align:center;margin:0 0 3px}
    .receipt-center{text-align:center}.receipt-rule{border:0;border-top:1px dashed #555;margin:8px 0}
    .receipt-row{display:flex;justify-content:space-between;gap:8px}.receipt-item{margin:6px 0}.receipt-item-name{font-weight:700;overflow-wrap:anywhere}
    .receipt-total{font-size:14px;font-weight:800}.receipt-muted{color:#555;font-size:11px}
    @media print{
        @page{size:80mm auto;margin:3mm}
        body{background:#fff!important;color:#000!important;padding:0!important}
        body *{visibility:hidden!important}
        #thermalReceipt,#thermalReceipt *{visibility:visible!important}
        #thermalReceipt{position:absolute;left:0;top:0;width:74mm;max-width:none;margin:0;padding:0}
        .receipt-actions{display:none!important}
    }
</style>
<div class="receipt-actions">
    <a class="btn btn-sm btn-outline-dark" href="{{ route('retail.pos.index') }}"><i class="bi bi-arrow-left me-1"></i>New sale</a>
    <button class="btn btn-sm btn-success" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print receipt</button>
</div>
<article class="thermal-receipt" id="thermalReceipt" aria-label="Receipt {{ $order->order_number }}">
    <h1>{{ $business?->name ?? config('app.name') }}</h1>
    <div class="receipt-center receipt-muted">{{ $order->retailExtension?->branch?->name ?? 'Retail sale' }}</div>
    <hr class="receipt-rule">
    <div>Receipt: {{ $order->order_number }}</div>
    <div>Date: {{ $order->created_at?->format('d/m/Y H:i') ?? $order->order_date?->format('d/m/Y') }}</div>
    <div>Cashier: {{ $order->retailExtension?->cashier?->name ?? auth()->user()?->name }}</div>
    <div>Customer: {{ $order->client?->name ?: ($order->customer_name ?: 'Walk-in') }}</div>
    @if($order->customer_phone)<div>Phone: {{ $order->customer_phone }}</div>@endif
    <hr class="receipt-rule">
    @foreach($order->items as $item)
        <div class="receipt-item">
            <div class="receipt-item-name">{{ $item->title ?: $item->description }}</div>
            @if($item->sku_snapshot)<div class="receipt-muted">SKU {{ $item->sku_snapshot }}</div>@endif
            <div class="receipt-row"><span>{{ rtrim(rtrim(number_format((float)$item->quantity, 3), '0'), '.') }} × {{ number_format((float)$item->unit_price, 2) }}</span><span>{{ number_format((float)$item->line_total, 2) }}</span></div>
        </div>
    @endforeach
    <hr class="receipt-rule">
    <div class="receipt-row"><span>Subtotal</span><span>{{ number_format((float)$order->subtotal, 2) }}</span></div>
    @if((float)$order->discount_total > 0)<div class="receipt-row"><span>Discount</span><span>-{{ number_format((float)$order->discount_total, 2) }}</span></div>@endif
    @if((float)$order->tax_total > 0)<div class="receipt-row"><span>Tax</span><span>{{ number_format((float)$order->tax_total, 2) }}</span></div>@endif
    <div class="receipt-row receipt-total"><span>TOTAL</span><span>{{ number_format((float)$order->total, 2) }}</span></div>
    <hr class="receipt-rule">
    @forelse($order->payments as $payment)
        <div class="receipt-row"><span>{{ $payment->paymentMethod?->name ?: 'Payment' }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</span><span>{{ number_format((float)$payment->amount, 2) }}</span></div>
    @empty
        <div class="receipt-row"><span>Payment received</span><span>{{ number_format((float)$order->amount_paid, 2) }}</span></div>
    @endforelse
    <div class="receipt-row"><span>Paid</span><span>{{ number_format((float)$order->amount_paid, 2) }}</span></div>
    @if((float)$order->total > (float)$order->amount_paid)<div class="receipt-row"><span>Balance</span><span>{{ number_format(max((float)$order->total - (float)$order->amount_paid, 0), 2) }}</span></div>@endif
    <hr class="receipt-rule">
    <div class="receipt-center">Thank you for shopping with us!</div>
    <div class="receipt-center receipt-muted">Goods sold are subject to store return policy.</div>
</article>
@endsection
