@extends('layouts.app')
@section('title', $purchaseOrder->po_number)
@section('content')
<div class="d-flex gap-2 justify-content-end mb-3">
    <a class="btn btn-outline-dark" href="{{ route('erp.procurement') }}">Back to Procurement</a>
    <a class="btn btn-success" href="{{ route('erp.purchase-orders.download', $purchaseOrder) }}"><i class="bi bi-download"></i> Download PO</a>
</div>
@include('erp.purchase-order-sheet')
@endsection
