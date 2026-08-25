@extends('layouts.app')
@section('title','Clients')
@section('content')
<div class="page-shell">
    <x-page-header title="Clients" subtitle="Customer records, contact details, and company profiles.">
        <x-slot:actions>
            <a class="btn btn-warning" href="{{ route('clients.create') }}"><i class="bi bi-person-plus"></i> Add Client</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            <x-responsive-table mobile-label="Clients">
                <table class="table align-middle">
                    <thead><tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th></th></tr></thead>
                    <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td><a href="{{ route('clients.show',$client) }}">{{ $client->name }}</a></td>
                            <td>{{ $client->company_name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->phone }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('clients.edit',$client) }}" aria-label="Edit {{ $client->name }}"><i class="bi bi-pencil"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No clients found.</td></tr>
                    @endforelse
                    </tbody>
                </table>

                <x-slot:mobile>
                    @forelse($clients as $client)
                        <x-mobile-record-card :title="$client->name" :href="route('clients.show',$client)">
                            <x-slot:meta>
                                <div><span>Company</span><strong>{{ $client->company_name ?: 'Individual' }}</strong></div>
                                <div><span>Email</span><strong>{{ $client->email ?: 'Not set' }}</strong></div>
                                <div><span>Phone</span><strong>{{ $client->phone ?: 'Not set' }}</strong></div>
                            </x-slot:meta>
                            <x-slot:actions>
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('clients.show',$client) }}"><i class="bi bi-eye"></i> View</a>
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('clients.edit',$client) }}"><i class="bi bi-pencil"></i> Edit</a>
                            </x-slot:actions>
                        </x-mobile-record-card>
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-people fs-3 text-success"></i>
                            <div>No clients found.</div>
                            <a class="btn btn-warning" href="{{ route('clients.create') }}">Add Client</a>
                        </div>
                    @endforelse
                </x-slot:mobile>
            </x-responsive-table>
            <div class="mt-3">{{ $clients->links() }}</div>
        </div>
    </div>
</div>
@endsection
