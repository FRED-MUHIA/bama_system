@extends('layouts.app')
@section('title','POS Orders')
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
    <form method="post" action="{{ route('pos-orders.import') }}" enctype="multipart/form-data" class="card flex-grow-1">
        @csrf
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-lg-7">
                    <label class="form-label">Upload orders CSV</label>
                    <input class="form-control" type="file" name="orders_csv" accept=".csv,text/csv" required>
                    <div class="small text-muted mt-1">Required columns: date, order, status, customer, customer type, items sold, net sales. Product columns are optional.</div>
                </div>
                <div class="col-lg-5 d-flex gap-2 justify-content-lg-end">
                    <button class="btn btn-outline-warning"><i class="bi bi-upload"></i> Import CSV</button>
                    <a class="btn btn-warning" href="{{ route('pos-orders.create') }}"><i class="bi bi-plus-circle"></i> New POS Order</a>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="card"><div class="card-body table-responsive"><table class="table align-middle">
<thead><tr><th>Date</th><th>Order</th><th>Status</th><th>Customer</th><th>Customer Type</th><th>Products</th><th>Items Sold</th><th>Net Sales</th><th></th></tr></thead><tbody>
@forelse($orders as $order)
<tr>
    <td>{{ $order->order_date?->format('d M Y') }}</td>
    <td><a href="{{ route('pos-orders.show',$order) }}">{{ $order->order_number }}</a></td>
    <td><span class="status-pill">{{ $order->status }}</span></td>
    <td>{{ $order->client?->name ?: ($order->customer_name ?: 'Walk-in') }}</td>
    <td>{{ $order->customer_type ?: '-' }}</td>
    <td>{{ $order->items->pluck('title')->filter()->join(', ') ?: $order->items->pluck('description')->filter()->join(', ') }}</td>
    <td>{{ number_format($order->items->sum('quantity'),2) }}</td>
    <td>{{ number_format($order->total,2) }}</td>
    <td class="text-end">
        <div class="btn-group btn-group-sm">
            <a class="btn btn-outline-dark" href="{{ route('pos-orders.show',$order) }}"><i class="bi bi-eye"></i></a>
            <a class="btn btn-outline-dark" href="{{ route('pos-orders.edit',$order) }}"><i class="bi bi-pencil"></i></a>
        </div>
    </td>
</tr>
@empty <tr><td colspan="9" class="text-muted">No POS orders yet.</td></tr>@endforelse
</tbody></table>{{ $orders->links() }}</div></div>
@endsection
