@extends('layouts.app')
@section('title','Clients')
@section('content')
<div class="d-flex justify-content-end mb-3"><a class="btn btn-warning" href="{{ route('clients.create') }}"><i class="bi bi-person-plus"></i> Add Client</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table align-middle">
<thead><tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th></th></tr></thead><tbody>
@forelse($clients as $client)
<tr><td><a href="{{ route('clients.show',$client) }}">{{ $client->name }}</a></td><td>{{ $client->company_name }}</td><td>{{ $client->email }}</td><td>{{ $client->phone }}</td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('clients.edit',$client) }}"><i class="bi bi-pencil"></i></a></td></tr>
@empty <tr><td colspan="5" class="text-muted">No clients found.</td></tr>@endforelse
</tbody></table>{{ $clients->links() }}</div></div>
@endsection
