<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt['number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #17251c; padding: 24px; }
        h1 { color: #008f46; font-size: 26px; margin-bottom: 6px; }
        .muted { color: #66736b; } .paid { color: #008f46; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        th, td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #dfe6e2; }
        th { width: 36%; } .total { font-size: 19px; background: #e7f7ee; }
    </style>
</head>
<body>
    <h1>Bama Solutions</h1>
    <h2>Subscription payment receipt</h2>
    <p class="paid">PAID</p>
    <table>
        <tr><th>Receipt number</th><td>{{ $receipt['number'] }}</td></tr>
        <tr><th>Invoice number</th><td>{{ $receipt['invoice_number'] }}</td></tr>
        <tr><th>Client</th><td>{{ $receipt['customer_name'] }}</td></tr>
        <tr><th>Package</th><td>{{ $receipt['plan_name'] }}</td></tr>
        <tr><th>Paid at</th><td>{{ $receipt['paid_at'] }} {{ $receipt['timezone'] }}</td></tr>
        <tr><th>Payment method</th><td>{{ $receipt['provider'] }}</td></tr>
        <tr><th>Transaction reference</th><td>{{ $receipt['reference'] ?: 'Not recorded' }}</td></tr>
        @if($receipt['renews_at'])<tr><th>Next renewal</th><td>{{ $receipt['renews_at'] }} {{ $receipt['timezone'] }}</td></tr>@endif
        <tr class="total"><th>Amount paid</th><td>{{ $receipt['currency'] }} {{ number_format((float) $receipt['amount'], 2) }}</td></tr>
    </table>
    <p>Thank you for your payment. Please keep this receipt for your records.</p>
    <p class="muted">This receipt acknowledges the subscription payment shown above.</p>
</body>
</html>
