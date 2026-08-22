@extends('layouts.app')
@section('title', 'Batch Tracking')

@section('content')
@include('retail.partials.nav')
<div class="card p-3 mb-3">
    <form method="POST" action="{{ route('retail.scanning.batches.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><select class="form-select" name="product_id" required><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input class="form-control" name="batch_number" placeholder="Batch number" required></div>
        <div class="col-md-2"><input class="form-control" name="manufacture_date" type="date"></div>
        <div class="col-md-2"><input class="form-control" name="expiry_date" type="date"></div>
        <div class="col-md-1"><input class="form-control" name="quantity" type="number" step="0.001" value="0"></div>
        <div class="col-md-1"><select class="form-select" name="status"><option>Active</option><option>Quarantined</option><option>Disabled</option></select></div>
        <input type="hidden" name="compliance_status" value="Compliant">
        <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
    </form>
</div>
<div class="card p-0">
    <table class="table mb-0"><thead><tr><th>Batch</th><th>Product</th><th>Expiry</th><th>Qty</th><th>Status</th><th></th></tr></thead><tbody>
        @forelse($batches as $batch)
            <tr>
                <td>{{ $batch->batch_number }}</td>
                <td>{{ $batch->product?->name }}</td>
                <td>{{ $batch->expiry_date?->format('d M Y') }}</td>
                <td>{{ number_format($batch->quantity, 3) }}</td>
                <td><span class="status-pill">{{ $batch->status }} · {{ $batch->recall_status }}</span></td>
                <td>
                    <form method="POST" action="{{ route('retail.scanning.batches.recall', $batch) }}" class="d-flex gap-1">
                        @csrf
                        <input class="form-control form-control-sm" name="reason" placeholder="Recall reason">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-exclamation-triangle"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted p-4">No product batches yet.</td></tr>
        @endforelse
    </tbody></table>
    <div class="p-3">{{ $batches->links() }}</div>
</div>
@endsection
