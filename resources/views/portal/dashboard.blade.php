@extends('layouts.app')
@section('title','Client Portal')
@section('content')
<div class="card mb-4"><div class="card-body"><h1 class="h5">{{ $invitation->client->name }}</h1><p class="text-muted mb-0">Projects, invoices, documents, payments, warranty, and approvals.</p></div></div>
<div class="row g-4"><div class="col-lg-6"><div class="card"><div class="card-body"><h2 class="h5">Projects</h2>@forelse($projects as $project)<div class="border-top py-2">{{ $project->project_name }}<span class="float-end">{{ $project->status }}</span><div class="small text-muted">{{ $project->documents->count() }} documents · {{ $project->warranties->count() }} warranties</div></div>@empty<div class="text-muted">No projects available.</div>@endforelse</div></div></div><div class="col-lg-6"><div class="card"><div class="card-body"><h2 class="h5">Invoices</h2>@forelse($invoices as $invoice)<div class="border-top py-2">{{ $invoice->invoice_number }}<span class="float-end">{{ number_format($invoice->balance,2) }} · {{ $invoice->payment_status }}</span></div>@empty<div class="text-muted">No invoices available.</div>@endforelse</div></div></div></div>
@endsection
