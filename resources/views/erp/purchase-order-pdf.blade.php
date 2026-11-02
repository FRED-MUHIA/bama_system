<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #17202a; font-size: 11px; }
    </style>
</head>
<body>
    @include('erp.purchase-order-sheet', ['pdf' => true])
</body>
</html>
