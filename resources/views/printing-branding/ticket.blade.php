@extends('layouts.app')
@section('title', 'Job Ticket '.$job->job_number)

@section('content')
<style>
    .ticket{background:#fff;border:1px solid #d8dde6;border-radius:12px;padding:24px;max-width:760px;min-height:1060px;margin:auto}
    .ticket-head{display:flex;justify-content:space-between;gap:16px;border-bottom:3px solid #00A651;padding-bottom:16px;margin-bottom:16px}
    .ticket-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .ticket-field{border:1px solid #edf0f4;border-radius:10px;padding:12px}
    .ticket-label{font-size:.72rem;font-weight:900;text-transform:uppercase;color:#667085}
    .ticket-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
    @media print{nav,.sidebar,.btn,.ticket-actions{display:none!important}.ticket{border:0;box-shadow:none;max-width:none;min-height:auto}}
    @media(max-width:700px){.ticket-grid{grid-template-columns:1fr}.ticket-head{display:grid}}
</style>
<div class="ticket">
    <div class="ticket-head">
        <div>
            <div class="ticket-label">Electronic Job Ticket</div>
            <h1 class="h2 mb-0">{{ $job->job_number }}</h1>
            <div class="text-muted">{{ $job->ticket?->ticket_number }}</div>
        </div>
        <div class="text-end">
            <div class="display-6"><i class="bi bi-qr-code"></i></div>
            <div class="small">{{ $job->ticket?->qr_token }}</div>
            <div class="fw-bold">{{ $job->ticket?->barcode }}</div>
        </div>
    </div>
    <div class="ticket-grid">
        @foreach([
            'Client' => $job->client?->name,
            'Product' => $job->product_name,
            'Quantity' => $job->quantity,
            'Machine' => $job->machine?->name,
            'Deadline' => $job->delivery_date?->format('d M Y'),
            'Priority' => $job->priority,
            'Status' => $job->status,
            'Artwork' => $job->artwork_path ?: 'Pending',
        ] as $label => $value)
            <div class="ticket-field">
                <div class="ticket-label">{{ $label }}</div>
                <strong>{{ $value ?: '-' }}</strong>
            </div>
        @endforeach
    </div>
    <div class="ticket-field mt-3">
        <div class="ticket-label">Specifications</div>
        <pre class="mb-0">{{ json_encode($job->specifications ?? [], JSON_PRETTY_PRINT) }}</pre>
    </div>
    <div class="ticket-field mt-3">
        <div class="ticket-label">Production Notes / Special Instructions</div>
        <p class="mb-0">{{ $job->production_notes ?: '-' }}</p>
    </div>
    <div class="ticket-actions">
        <button class="btn btn-dark" onclick="window.print()"><i class="bi bi-printer"></i> Print Ticket</button>
        <a class="btn btn-outline-dark" href="{{ route('printing-branding.tickets.pdf', $job) }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Open PDF</a>
        <a class="btn btn-outline-warning" href="{{ route('printing-branding.tickets.download', $job) }}"><i class="bi bi-download"></i> Download PDF</a>
    </div>
</div>
@endsection
