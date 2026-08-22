@extends('layouts.app')
@section('title','Letters')
@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <form class="d-flex flex-wrap gap-2" method="get" action="{{ route('letters.index') }}">
        <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search client, project, invoice, subject, number">
        <select class="form-select" name="type"><option value="">Type</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('type')===$type)>{{ $type }}</option>@endforeach</select>
        <select class="form-select" name="status"><option value="">Status</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select>
        <input class="form-control" name="date" type="date" value="{{ request('date') }}">
        <button class="btn btn-outline-warning"><i class="bi bi-search"></i></button>
    </form>
    <a class="btn btn-warning" href="{{ route('letters.create') }}"><i class="bi bi-plus-circle"></i> New Letter</a>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card"><div class="card-body">
            <h2 class="h5">Business Correspondence</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Letter</th><th>Client</th><th>Linked To</th><th>Type</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($letters as $letter)
                        <tr>
                            <td><a href="{{ route('letters.show',$letter) }}">{{ $letter->letter_number }}</a><div class="small text-muted">{{ $letter->subject }}</div></td>
                            <td>{{ $letter->client?->name ?: '-' }}</td>
                            <td>
                                @if($letter->project)<a href="{{ route('projects.show',$letter->project) }}">{{ $letter->project->project_name }}</a>
                                @elseif($letter->invoice)<a href="{{ route('invoices.show',$letter->invoice) }}">{{ $letter->invoice->invoice_number }}</a>
                                @elseif($letter->receipt)<a href="{{ route('receipts.show',$letter->receipt) }}">{{ $letter->receipt->receipt_number }}</a>
                                @elseif($letter->warranty)Warranty: {{ $letter->warranty->warranty_number }}
                                @else - @endif
                            </td>
                            <td><span class="badge bg-warning text-dark">{{ $letter->type }}</span></td>
                            <td><span class="status-pill">{{ $letter->status }}</span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-warning" href="{{ route('letters.show',$letter) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No letters yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $letters->links() }}
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h5">Quick Create</h2>
            @foreach(['Financial','Project','Legal','Warranty','General'] as $cat)
                <div class="border-top py-1">
                    <div class="small fw-bold text-muted mt-1">{{ $cat }}</div>
                    @foreach($templates->where('type',$cat)->take(4) as $template)
                        <a href="{{ route('letters.create',['template_id' => $template->id]) }}" class="d-block small py-1">{{ $template->name }}</a>
                    @endforeach
                </div>
            @endforeach
        </div></div>
        <div class="card"><div class="card-body">
            <h2 class="h5">Reusable Templates</h2>
            @foreach($templates as $template)
                <div class="border-top py-2"><strong>{{ $template->name }}</strong><span class="float-end badge bg-warning text-dark">{{ $template->type }}</span><div class="small text-muted">{{ $template->default_subject }}</div></div>
            @endforeach
            {{ $templates->links() }}
            <form class="border-top mt-3 pt-3" method="post" action="{{ route('letters.templates.store') }}">
                @csrf
                <input class="form-control mb-2" name="name" placeholder="Template name" required>
                <select class="form-select mb-2" name="type">@foreach($types as $type)<option>{{ $type }}</option>@endforeach</select>
                <input class="form-control mb-2" name="default_subject" placeholder="Subject with placeholders">
                <textarea class="form-control mb-2" name="content" rows="4" placeholder="Content with @{{client_name}}, @{{invoice_number}}, @{{invoice_balance}}" required></textarea>
                <select class="form-select mb-2" name="output_format"><option>PDF</option><option>DOCX</option></select>
                <label class="form-check mb-2"><input class="form-check-input" name="is_active" value="1" type="checkbox" checked> Active</label>
                <button class="btn btn-outline-warning btn-sm">Save Template</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
