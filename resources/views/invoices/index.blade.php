@extends('layouts.app')
@section('title','Invoices')
@section('content')
@php $partPaymentsEnabled = \App\Models\Invoice::supportsPartPayments(); @endphp
<div class="d-flex justify-content-end mb-3"><a class="btn btn-warning" href="{{ route('invoices.create') }}"><i class="bi bi-plus-circle"></i> New Invoice</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table align-middle"><thead><tr><th>Number</th><th>Client</th><th>Due</th><th>Total</th>@if($partPaymentsEnabled)<th>Part Payments</th>@endif<th>Balance</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($invoices as $invoice)<tr><td><a href="{{ route('invoices.show',$invoice) }}">{{ $invoice->invoice_number }}</a></td><td>{{ $invoice->client->name }}</td><td>{{ $invoice->due_date?->format('d M Y') }}</td><td>{{ number_format($invoice->total,2) }}</td>@if($partPaymentsEnabled)<td>{{ number_format($invoice->partPaymentInvoices->sum('part_payment_amount'),2) }}</td>@endif<td>{{ number_format($invoice->balance,2) }}</td><td><span class="status-pill">{{ $invoice->payment_status }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.edit',$invoice) }}"><i class="bi bi-pencil"></i></a></td></tr>@empty<tr><td colspan="{{ $partPaymentsEnabled ? 8 : 7 }}" class="text-muted">No invoices yet.</td></tr>@endforelse
</tbody></table>{{ $invoices->links() }}</div></div>
@endsection
