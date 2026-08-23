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
        .public-document{padding:28px 14px}
        @media(max-width:760px){.public-document{padding:0}}
    </style>
</head>
<body>
<main class="public-document">
    @include('documents.document-sheet', [
        'type' => 'Invoice',
        'document' => $invoice,
        'publicDownloadUrl' => route('public.invoices.download', $invoice->public_token),
    ])
</main>
</body>
</html>
