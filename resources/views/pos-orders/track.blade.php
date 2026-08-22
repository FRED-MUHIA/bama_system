<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track {{ $order->order_number }} - BAMA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f5f7fb; }
        .page { max-width:860px; margin:38px auto; }
        .card { border:0; border-radius:8px; box-shadow:0 8px 24px rgba(15,23,42,.08); }
        .brand { color:#f97316; font-weight:800; }
        .status-pill { border-radius:999px; padding:.35rem .75rem; background:#fff7ed; color:#9a3412; display:inline-block; }
    </style>
</head>
<body>
<main class="page px-3">
    <div class="mb-3"><div class="brand h3 mb-0">BAMA</div><div class="text-muted">Order tracking</div></div>
    <div class="card"><div class="card-body p-4">
        <div class="row g-4 mb-3">
            <div class="col-md-6"><h1 class="h4">{{ $order->order_number }}</h1><p class="mb-1">Tracking key: <strong>{{ $order->tracking_key }}</strong></p><p class="mb-1">Date: {{ $order->order_date?->format('d M Y') }}</p><span class="status-pill">{{ $order->status }}</span></div>
            <div class="col-md-6"><h2 class="h5">Customer</h2><p class="mb-1">{{ $order->client?->name ?: ($order->customer_name ?: 'Walk-in') }}</p><p class="mb-1">{{ $order->customer_phone }}</p><p class="text-muted">{{ $order->customer_address }}</p></div>
        </div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead><tbody>
            @foreach($order->items as $item)<tr><td>{{ $item->product?->name ?: ($item->title ?: $item->description) }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->line_total,2) }}</td></tr>@endforeach
        </tbody></table></div>
        <div class="row justify-content-end"><div class="col-md-5"><table class="table"><tr><th>Total</th><td class="text-end fw-bold">{{ number_format($order->total,2) }}</td></tr><tr><th>Paid</th><td class="text-end">{{ number_format($order->amount_paid,2) }}</td></tr></table></div></div>
    </div></div>
</main>
</body>
</html>
