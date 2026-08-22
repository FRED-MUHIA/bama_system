@extends('layouts.app')
@section('title','Receipts')
@section('content')
<div class="card"><div class="card-body table-responsive"><table class="table align-middle"><thead><tr><th>Number</th><th>Client</th><th>Invoice</th><th>Date</th><th>Amount</th><th></th></tr></thead><tbody>
@forelse($receipts as $receipt)<tr><td><a href="{{ route('receipts.show',$receipt) }}">{{ $receipt->receipt_number }}</a></td><td>{{ $receipt->invoice->client->name }}</td><td>{{ $receipt->invoice->invoice_number }}</td><td>{{ $receipt->payment_date?->format('d M Y') }}</td><td>{{ number_format($receipt->amount_paid,2) }}</td><td class="text-end"><a class="btn btn-sm btn-outline-warning" href="{{ route('receipts.download',$receipt) }}"><i class="bi bi-download"></i></a></td></tr>@empty<tr><td colspan="6" class="text-muted">No receipts yet.</td></tr>@endforelse
</tbody></table>{{ $receipts->links() }}</div></div>
@endsection
