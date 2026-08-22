@extends('layouts.app')
@section('title', 'Mobile Job '.$job->job_number)

@section('content')
<style>
    .mobile-job{max-width:680px;margin:auto;display:grid;gap:14px}
    .mobile-panel{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:16px}
    .mobile-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .mobile-actions button,.mobile-actions a{padding:14px;font-weight:900;border-radius:10px}
    @media(max-width:520px){.mobile-actions{grid-template-columns:1fr}}
</style>
<div class="mobile-job">
    <div class="mobile-panel">
        <div class="text-muted small">Production Job</div>
        <h1 class="h3">{{ $job->job_number }}</h1>
        <strong>{{ $job->client?->name }}</strong>
        <div>{{ $job->product_name }} · {{ number_format((float) $job->quantity, 0) }}</div>
        <span class="badge bg-success mt-2">{{ $job->status }}</span>
    </div>
    <div class="mobile-panel">
        <h2 class="h6">Artwork Preview</h2>
        <div class="border rounded p-4 text-center text-muted">{{ $job->artworks->last()?->file_path ?: $job->artwork_path ?: 'No artwork uploaded' }}</div>
    </div>
    <div class="mobile-panel">
        <h2 class="h6">Specifications</h2>
        <pre class="mb-0">{{ json_encode($job->specifications ?? [], JSON_PRETTY_PRINT) }}</pre>
    </div>
    <div class="mobile-panel mobile-actions">
        @foreach(['In Production' => 'Start Job', 'On Hold' => 'Pause', 'Completed' => 'Complete', 'Quality Control' => 'Report Problem'] as $status => $label)
            <form method="post" action="{{ route('printing-branding.jobs.status', $job) }}">
                @csrf
                <input type="hidden" name="status" value="{{ $status }}">
                <button class="btn btn-dark w-100">{{ $label }}</button>
            </form>
        @endforeach
        <a class="btn btn-outline-dark" href="{{ route('printing-branding.waste', ['job_id' => $job->id]) }}">Record Waste</a>
        <a class="btn btn-outline-dark" href="{{ route('printing-branding.artwork') }}">Upload Photo</a>
    </div>
</div>
@endsection
