@extends('layouts.app')
@section('title',$letter->letter_number)
@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
    <a class="btn btn-outline-dark" href="{{ route('letters.edit',$letter) }}"><i class="bi bi-pencil"></i> Edit</a>
    <form method="post" action="{{ route('letters.submit',$letter) }}">@csrf<button class="btn btn-outline-warning" @disabled($letter->status !== 'Draft')>Submit</button></form>
    <form method="post" action="{{ route('letters.approve',$letter) }}">@csrf<button class="btn btn-warning" @disabled(! in_array($letter->status, ['Draft','Pending'], true))><i class="bi bi-check2-circle"></i> Approve</button></form>
    <a class="btn btn-outline-warning" href="{{ route('letters.download',[$letter,'pdf']) }}"><i class="bi bi-file-pdf"></i> PDF</a>
    <a class="btn btn-outline-warning" href="{{ route('letters.download',[$letter,'docx']) }}"><i class="bi bi-file-word"></i> DOCX</a>
    <a class="btn btn-outline-primary" href="{{ route('letters.preview',$letter) }}" target="_blank"><i class="bi bi-eye"></i> Preview</a>
    <a class="btn btn-outline-primary" href="{{ route('letters.delivery',$letter) }}"><i class="bi bi-send"></i> Send</a>
    <form method="post" action="{{ route('letters.archive',$letter) }}">@csrf<button class="btn btn-outline-secondary">Archive</button></form>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small mb-1">{{ $letter->type }}</div>
                        <h2 class="h4 mb-0">{{ $letter->subject }}</h2>
                    </div>
                    <span class="status-pill flex-shrink-0">{{ $letter->status }}</span>
                </div>
                <hr>
                @if($letter->content_type === 'html')
                    <div>{!! $letter->content !!}</div>
                @else
                    <div style="white-space:pre-wrap; font-size:14px; line-height:1.7;">{{ $letter->content }}</div>
                @endif

                @if($signatory)
                <hr>
                <div class="row mt-4">
                    <div class="col-md-7">
                        <p>Yours sincerely,</p>
                        <br>
                        @if($signatory->signatureUrl())
                            <img src="{{ $signatory->signatureUrl() }}" style="max-height:60px; display:block; margin-bottom:4px;">
                        @endif
                        <p class="mb-1 fw-bold">{{ $signatory->name }}</p>
                        <p class="text-muted small">{{ $signatory->title }}</p>
                    </div>
                    <div class="col-md-5 text-end">
                        @php
                            try {
                                $qrUrl = route('public.letters.verify', $letter->id);
                                $builder = new \Endroid\QrCode\Builder\Builder(
                                    writer: new \Endroid\QrCode\Writer\SvgWriter(),
                                    data: $qrUrl,
                                    errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Medium,
                                    size: 100,
                                    margin: 5,
                                    foregroundColor: new \Endroid\QrCode\Color\Color(17, 24, 39),
                                    backgroundColor: new \Endroid\QrCode\Color\Color(255, 255, 255),
                                );
                                $qrDataUri = $builder->build()->getDataUri();
                            } catch (\Throwable $e) { $qrDataUri = null; }
                        @endphp
                        @if($qrDataUri)
                            <div>
                                <img src="{{ $qrDataUri }}" style="width:86px;height:86px;border:1px solid #e5e7eb;padding:4px;">
                                <div class="text-muted small mt-1">Scan to verify</div>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h5">Tracking</h2>
            <p class="mb-1"><strong>Created:</strong> {{ $letter->created_at?->format('d M Y H:i') }}</p>
            <p class="mb-1"><strong>By:</strong> {{ $letter->creator?->name ?: '-' }}</p>
            <p class="mb-1"><strong>Approved:</strong> {{ $letter->approved_at?->format('d M Y H:i') ?: '-' }}</p>
            <p class="mb-1"><strong>Sent:</strong> {{ $letter->sent_at?->format('d M Y H:i') ?: '-' }}</p>
            <p class="mb-1"><strong>Recipient:</strong> {{ $letter->recipient ?: '-' }}</p>
            <p class="mb-0"><strong>Delivery:</strong> {{ $letter->delivery_status ?: 'not sent' }}</p>
        </div></div>
        <div class="card mb-3"><div class="card-body">
            <h2 class="h5">Linked Records</h2>
            @if($letter->client)<p class="mb-1">Client: <a href="{{ route('clients.show',$letter->client) }}">{{ $letter->client->name }}</a></p>@endif
            @if($letter->site)<p class="mb-1">Site: {{ $letter->site->site_name }}</p>@endif
            @if($letter->project)<p class="mb-1">Project: <a href="{{ route('projects.show',$letter->project) }}">{{ $letter->project->project_name }}</a></p>@endif
            @if($letter->invoice)<p class="mb-1">Invoice: <a href="{{ route('invoices.show',$letter->invoice) }}">{{ $letter->invoice->invoice_number }}</a></p>@endif
            @if($letter->receipt)<p class="mb-0">Receipt: <a href="{{ route('receipts.show',$letter->receipt) }}">{{ $letter->receipt->receipt_number }}</a></p>@endif
            @if($letter->payment)<p class="mb-0">Payment: {{ number_format($letter->payment->amount,2) }}</p>@endif
            @if($letter->warranty)<p class="mb-0">Warranty: {{ $letter->warranty->warranty_number }}</p>@endif
        </div></div>
        <div class="card"><div class="card-body">
            <h2 class="h5">Verification</h2>
            <p class="mb-1"><strong>Verify online:</strong></p>
            <a href="{{ route('public.letters.verify', $letter) }}" target="_blank" class="small">{{ route('public.letters.verify', $letter) }}</a>
        </div></div>
        <div class="card mt-3"><div class="card-body">
            <h2 class="h5">Version History</h2>
            @forelse($letter->versions->sortByDesc('version') as $version)
                <div class="border-top py-2">v{{ $version->version }} <span class="float-end">{{ $version->status }}</span><div class="small text-muted">{{ $version->created_at?->format('d M Y H:i') }}</div></div>
            @empty
                <div class="text-muted">No versions yet.</div>
            @endforelse
        </div></div>
    </div>
</div>
@endsection
