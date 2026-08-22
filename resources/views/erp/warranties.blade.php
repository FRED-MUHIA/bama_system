@extends('layouts.app')
@section('title','Warranty')
@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h2 class="h5">Add Warranty</h2>
            <form method="post" action="{{ route('erp.warranties.store') }}">
                @csrf
                <select class="form-select mb-2" name="client_id"><option value="">Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                <select class="form-select mb-2" name="project_id"><option value="">Project</option>@foreach($clients as $client)@foreach($client->projects as $project)<option value="{{ $project->id }}">{{ $project->project_name }}</option>@endforeach @endforeach</select>
                <input class="form-control mb-2" name="manufacturer" placeholder="Manufacturer">
                <input class="form-control mb-2" name="serial_number" placeholder="Serial">
                <input class="form-control mb-2" name="expires_at" type="date">
                <select class="form-select mb-2" name="status"><option>Active</option><option>Claim Open</option><option>Resolved</option></select>
                <textarea class="form-control mb-2" name="notes"></textarea>
                <button class="btn btn-warning btn-sm">Save</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <h2 class="h5">Warranties</h2>
            @foreach($warranties as $warranty)
                <div class="border-top py-2">
                    <strong>{{ $warranty->manufacturer }} {{ $warranty->serial_number }}</strong><span class="float-end">{{ $warranty->status }}</span>
                    <div class="small text-muted">{{ $warranty->client?->name }} {{ $warranty->expires_at?->format('d M Y') }}</div>
                    @if(\Illuminate\Support\Facades\Schema::hasTable('letters'))
                        <div class="mt-2">
                            <a class="btn btn-outline-warning btn-sm" href="{{ route('letters.from-warranty',$warranty) }}"><i class="bi bi-envelope-paper"></i> Warranty Letter</a>
                            @foreach($warranty->letters as $letter)<a class="btn btn-sm btn-outline-dark" href="{{ route('letters.show',$letter) }}">{{ $letter->letter_number }}</a>@endforeach
                        </div>
                    @endif
                    <form class="mt-2" method="post" action="{{ route('erp.warranty-claims.store',$warranty) }}">
                        @csrf
                        <div class="row g-2"><div class="col-md-3"><input class="form-control form-control-sm" name="claim_date" type="date" value="{{ now()->format('Y-m-d') }}"></div><div class="col-md-3"><select class="form-select form-select-sm" name="status"><option>Claim Open</option><option>Resolved</option></select></div><div class="col-md-4"><input class="form-control form-control-sm" name="issue" placeholder="Issue"></div><div class="col-md-2"><button class="btn btn-outline-warning btn-sm">Claim</button></div></div>
                    </form>
                </div>
            @endforeach
            {{ $warranties->links() }}
        </div></div>
    </div>
</div>
@endsection
