@extends('layouts.app')
@section('title','Quotations')
@section('content')
<div class="d-flex justify-content-end mb-3"><a class="btn btn-warning" href="{{ route('quotations.create') }}"><i class="bi bi-plus-circle"></i> New Quotation</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table align-middle"><thead><tr><th>Number</th><th>Client</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($quotations as $quotation)<tr><td><a href="{{ route('quotations.show',$quotation) }}">{{ $quotation->quotation_number }}</a></td><td>{{ $quotation->client->name }}</td><td>{{ $quotation->quotation_date?->format('d M Y') }}</td><td>{{ number_format($quotation->total,2) }}</td><td><span class="status-pill">{{ $quotation->status }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('quotations.edit',$quotation) }}"><i class="bi bi-pencil"></i></a></td></tr>@empty<tr><td colspan="6" class="text-muted">No quotations yet.</td></tr>@endforelse
</tbody></table>{{ $quotations->links() }}</div></div>
@endsection
