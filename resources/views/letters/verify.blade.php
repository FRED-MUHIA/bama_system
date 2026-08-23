<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $documentColors = $company?->documentColors() ?? [
            'primary' => \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR,
            'secondary' => \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR,
            'accent' => \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR,
        ];
        $primaryColor = $documentColors['primary'];
        $secondaryColor = $documentColors['secondary'];
        $accentColor = $documentColors['accent'];
    @endphp
    <title>Document Verification - {{ $company?->company_name ?? 'BAMA' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; display: flex; align-items: center; min-height: 100vh; }
        .verify-card { max-width: 520px; margin: 0 auto; border: 0; border-radius: 12px; box-shadow: 0 12px 40px rgba(15,23,42,.08); }
        .verified-badge { display: inline-flex; align-items: center; gap: 8px; background: {{ $accentColor }}; color: {{ $secondaryColor }}; padding: 8px 16px; border-radius: 999px; font-weight: 700; font-size: 14px; }
        .verified-badge i { font-size: 18px; }
        .detail-label { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        .detail-value { color: {{ $secondaryColor }}; font-weight: 600; font-size: 14px; }
        .brand-logo { max-height: 58px; max-width: 130px; object-fit: contain; }
        .seal { width: 64px; height: 64px; border-radius: 50%; background: {{ $primaryColor }}; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card verify-card p-4 text-center">
            @if($company?->logoUrl())
                <img class="brand-logo mx-auto mb-3" src="{{ $company->logoUrl() }}" alt="{{ $company?->company_name ?? 'BAMA' }} logo">
            @else
                <div class="seal mx-auto mb-3">&#10003;</div>
            @endif
            <div class="verified-badge mx-auto mb-4">
                <span>&#10003;</span> DOCUMENT VERIFIED
            </div>
            <p class="text-muted mb-4">This document was issued by {{ $company?->company_name ?? 'BAMA' }} and has been verified as authentic.</p>
            <div class="border-top pt-4">
                <div class="row g-3 text-start">
                    <div class="col-6">
                        <div class="detail-label">Document Number</div>
                        <div class="detail-value">{{ $letter->letter_number }}</div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Document Type</div>
                        <div class="detail-value">{{ $letter->type }}</div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Issue Date</div>
                        <div class="detail-value">{{ $letter->created_at?->format('F j, Y') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">{{ $letter->status }}</div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Subject</div>
                        <div class="detail-value" style="font-size:12px;">{{ $letter->subject }}</div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Prepared By</div>
                        <div class="detail-value">{{ $company?->company_name ?? 'BAMA' }}</div>
                    </div>
                </div>
            </div>
            <div class="border-top mt-4 pt-3">
                <p class="mb-0 text-muted small">This verification confirms the digital authenticity of this document as issued by {{ $company?->company_name ?? 'BAMA' }}.</p>
            </div>
        </div>
    </div>
</body>
</html>
