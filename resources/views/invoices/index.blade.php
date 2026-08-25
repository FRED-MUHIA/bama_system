@extends('layouts.app')
@section('title','Invoices')
@section('content')
@php $partPaymentsEnabled = \App\Models\Invoice::supportsPartPayments(); @endphp
<div class="page-shell">
    <x-page-header title="Invoices" subtitle="Track billing, balances, payment status, and downloadable documents.">
        <x-slot:actions>
            <a class="btn btn-warning" href="{{ route('invoices.create') }}"><i class="bi bi-plus-circle"></i> New Invoice</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            <x-responsive-table mobile-label="Invoices">
                <table class="table align-middle">
                    <thead><tr><th>Number</th><th>Client</th><th>Due</th><th>Total</th>@if($partPaymentsEnabled)<th>Part Payments</th>@endif<th>Balance</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><a href="{{ route('invoices.show',$invoice) }}">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ $invoice->client->name }}</td>
                            <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                            <td>{{ number_format($invoice->total,2) }}</td>
                            @if($partPaymentsEnabled)<td>{{ number_format($invoice->partPaymentInvoices->sum('part_payment_amount'),2) }}</td>@endif
                            <td>{{ number_format($invoice->balance,2) }}</td>
                            <td><span class="status-pill">{{ $invoice->payment_status }}</span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.edit',$invoice) }}" aria-label="Edit {{ $invoice->invoice_number }}"><i class="bi bi-pencil"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $partPaymentsEnabled ? 8 : 7 }}" class="text-muted">No invoices yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>

                <x-slot:mobile>
                    @forelse($invoices as $invoice)
                        <x-mobile-record-card :title="$invoice->invoice_number" :href="route('invoices.show',$invoice)">
                            <x-slot:badge><span class="status-pill">{{ $invoice->payment_status }}</span></x-slot:badge>
                            <x-slot:meta>
                                <div><span>Client</span><strong>{{ $invoice->client->name }}</strong></div>
                                <div><span>Due</span><strong>{{ $invoice->due_date?->format('d M Y') ?: 'Not set' }}</strong></div>
                                <div><span>Total</span><strong>{{ number_format($invoice->total,2) }}</strong></div>
                                <div><span>Balance</span><strong>{{ number_format($invoice->balance,2) }}</strong></div>
                                @if($partPaymentsEnabled)
                                    <div><span>Part Payments</span><strong>{{ number_format($invoice->partPaymentInvoices->sum('part_payment_amount'),2) }}</strong></div>
                                @endif
                            </x-slot:meta>
                            <x-slot:actions>
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.show',$invoice) }}"><i class="bi bi-eye"></i> View</a>
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.edit',$invoice) }}"><i class="bi bi-pencil"></i> Edit</a>
                            </x-slot:actions>
                        </x-mobile-record-card>
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-receipt fs-3 text-success"></i>
                            <div>No invoices yet.</div>
                            <a class="btn btn-warning" href="{{ route('invoices.create') }}">New Invoice</a>
                        </div>
                    @endforelse
                </x-slot:mobile>
            </x-responsive-table>
            <div class="mt-3">{{ $invoices->links() }}</div>
        </div>
    </div>
</div>
@endsection
