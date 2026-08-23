@php
    $issuerProfile = $invoice->issuer_profile ?? [];
    $companyName = $issuerProfile['name'] ?? $settings?->company_name ?? 'BAMA';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }} - {{ $companyName }}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#eef2f7;color:#111827;font-family:Arial,Helvetica,sans-serif}
        .public-toolbar{position:sticky;top:0;z-index:5;background:#fff;border-bottom:1px solid #e5e7eb;padding:12px 18px;display:flex;justify-content:flex-end}
        .public-toolbar a{display:inline-flex;align-items:center;gap:8px;background:#111827;color:#fff;text-decoration:none;border-radius:7px;padding:10px 18px;font-weight:700}
        .public-document{padding:28px 14px}
        @media(max-width:760px){.public-document{padding:0}.public-toolbar{position:static}}
    </style>
</head>
<body>
<div class="public-toolbar">
    <a href="{{ route('public.invoices.download', $invoice->public_token) }}">Download PDF</a>
</div>
<main class="public-document">
    @include('documents.document-sheet', ['type' => 'Invoice', 'document' => $invoice])
</main>
</body>
</html>
