@extends('layouts.app')
@section('title','Procurement')

@section('content')
<style>
    .procurement-grid{display:grid;grid-template-columns:repeat(4,minmax(260px,1fr));gap:16px}
    .procurement-card{background:#fffdfa;border:1px solid #dedbd5;border-radius:12px;padding:18px}
    .procurement-card h2{font-size:1.05rem;margin-bottom:12px}
    .procurement-card .form-label{font-size:.72rem;font-weight:800;text-transform:uppercase;color:#5e5a56;letter-spacing:.06em}
    .supplier-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px}
    .supplier-tile{border:1px solid #dedbd5;border-radius:10px;background:#fff;padding:12px}
    @media(max-width:1400px){.procurement-grid{grid-template-columns:repeat(2,minmax(260px,1fr))}}
    @media(max-width:700px){.procurement-grid{grid-template-columns:1fr}}
</style>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="procurement-card h-100">
            <h2>Supplier</h2>
            <form method="post" action="{{ route('erp.suppliers.store') }}">
                @csrf
                <label class="form-label">Supplier name</label>
                <input class="form-control mb-2" name="name" placeholder="Supplier name" required>
                <label class="form-label">Email</label>
                <input class="form-control mb-2" name="email" type="email" placeholder="Email">
                <label class="form-label">Phone</label>
                <input class="form-control mb-2" name="phone" placeholder="Phone">
                <label class="form-label">KRA PIN</label>
                <input class="form-control mb-2" name="kra_pin" placeholder="KRA PIN">
                <label class="form-label">Address</label>
                <textarea class="form-control mb-2" name="address" placeholder="Address" rows="3"></textarea>
                <button class="btn btn-success btn-sm">Save Supplier</button>
            </form>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="procurement-card h-100">
            <h2>Suppliers</h2>
            <div class="supplier-list">
                @forelse($suppliers as $supplier)
                    <div class="supplier-tile">
                        <strong>{{ $supplier->name }}</strong>
                        <div class="small text-muted">{{ $supplier->phone ?: 'No phone' }}</div>
                        <div class="small text-muted">{{ $supplier->email ?: 'No email' }}</div>
                    </div>
                @empty
                    <div class="text-muted">No suppliers yet.</div>
                @endforelse
            </div>
            <div class="mt-3">{{ $suppliers->links() }}</div>
        </div>
    </div>
</div>

<div class="procurement-grid mt-4">
    <div class="procurement-card">
        <h2>Supplier Quote</h2>
        <form method="post" action="{{ route('erp.supplier-quotes.store') }}">
            @csrf
            @include('erp.partials.supplier-project-fields')
            <label class="form-label">Quote number</label>
            <input class="form-control mb-2" name="quote_number" placeholder="Quote number">
            <label class="form-label">Amount</label>
            <input class="form-control mb-2" name="amount" type="number" step="0.01" placeholder="Amount" required>
            <label class="form-label">Status</label>
            <select class="form-select mb-2" name="status">
                <option>Draft</option>
                <option>Accepted</option>
                <option>Rejected</option>
            </select>
            <label class="form-label">Notes</label>
            <textarea class="form-control mb-2" name="notes" placeholder="Notes" rows="3"></textarea>
            <button class="btn btn-outline-success btn-sm">Save Quote</button>
        </form>
    </div>

    <div class="procurement-card">
        <h2>Purchase Order</h2>
        <form method="post" action="{{ route('erp.purchase-orders.store') }}">
            @csrf
            @include('erp.partials.supplier-project-fields')
            <label class="form-label">PO number</label>
            <input class="form-control mb-2" name="po_number" placeholder="PO number" required>
            <label class="form-label">Order date</label>
            <input class="form-control mb-2" name="order_date" type="date">
            <label class="form-label">Amount</label>
            <input class="form-control mb-2" name="amount" type="number" step="0.01" placeholder="Amount" required>
            <label class="form-label">Status</label>
            <select class="form-select mb-2" name="status">
                <option>Draft</option>
                <option>Issued</option>
                <option>Closed</option>
            </select>
            <label class="form-label">Notes</label>
            <textarea class="form-control mb-2" name="notes" placeholder="Notes" rows="3"></textarea>
            <button class="btn btn-outline-success btn-sm">Save PO</button>
        </form>
    </div>

    <div class="procurement-card">
        <h2>Goods Received</h2>
        <form method="post" action="{{ route('erp.goods-received.store') }}">
            @csrf
            <label class="form-label">Purchase order</label>
            <select class="form-select mb-2" name="purchase_order_id" required>
                <option value="">Choose PO</option>
                @foreach($purchaseOrders as $po)
                    <option value="{{ $po->id }}">{{ $po->po_number }} - {{ $po->supplier?->name }} - {{ number_format($po->amount, 2) }}</option>
                @endforeach
            </select>
            <label class="form-label">Product / stock item</label>
            <select class="form-select mb-2" name="product_id">
                <option value="">No stock update</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} - Remaining {{ $product->formattedStock() }}</option>
                @endforeach
            </select>
            <label class="form-label">Received date</label>
            <input class="form-control mb-2" name="received_date" type="date" value="{{ now()->format('Y-m-d') }}" required>
            <label class="form-label">Quantity received</label>
            <input class="form-control mb-2" name="quantity_received" type="number" min="0" step="0.001" placeholder="Quantity in selected product unit">
            <label class="form-label">Unit cost</label>
            <input class="form-control mb-2" name="unit_cost" type="number" min="0" step="0.01" placeholder="Unit cost">
            <label class="form-label">Received by</label>
            <input class="form-control mb-2" name="received_by" placeholder="Received by">
            <label class="form-label">Notes</label>
            <textarea class="form-control mb-2" name="notes" placeholder="Goods condition, quantity notes, delivery reference" rows="5"></textarea>
            <button class="btn btn-outline-success btn-sm">Save Goods Received</button>
        </form>
        <hr>
        <h3 class="h6">Recent Receipts</h3>
        @forelse($goodsReceived as $receipt)
            <div class="border-top py-2">
                <strong>{{ $receipt->purchaseOrder?->po_number }}</strong>
                <span class="float-end">{{ $receipt->product?->formattedStock((float) $receipt->quantity_received) ?? number_format($receipt->quantity_received, 3) }}</span>
                <div class="small text-muted">{{ $receipt->product?->name ?? 'No stock item' }} · {{ $receipt->received_date?->format('d M Y') }}</div>
            </div>
        @empty
            <div class="text-muted small">No goods received yet.</div>
        @endforelse
    </div>

    <div class="procurement-card">
        <h2>Supplier Invoice</h2>
        <form method="post" action="{{ route('erp.supplier-invoices.store') }}">
            @csrf
            @include('erp.partials.supplier-project-fields')
            <label class="form-label">Purchase order</label>
            <select class="form-select mb-2" name="purchase_order_id">
                <option value="">No PO</option>
                @foreach($purchaseOrders as $po)
                    <option value="{{ $po->id }}">{{ $po->po_number }} - {{ $po->supplier?->name }}</option>
                @endforeach
            </select>
            <label class="form-label">Invoice number</label>
            <input class="form-control mb-2" name="invoice_number" placeholder="Invoice number" required>
            <label class="form-label">Invoice date</label>
            <input class="form-control mb-2" name="invoice_date" type="date">
            <label class="form-label">Due date</label>
            <input class="form-control mb-2" name="due_date" type="date">
            <label class="form-label">Total</label>
            <input class="form-control mb-2" name="total" type="number" step="0.01" placeholder="Total" required>
            <label class="form-label">Amount paid</label>
            <input class="form-control mb-2" name="amount_paid" type="number" step="0.01" placeholder="Amount paid">
            <label class="form-label">Status</label>
            <select class="form-select mb-2" name="status">
                <option>Draft</option>
                <option>Partial</option>
                <option>Paid</option>
            </select>
            <label class="form-label">Notes</label>
            <textarea class="form-control mb-2" name="notes" placeholder="Notes" rows="3"></textarea>
            <button class="btn btn-outline-success btn-sm">Save Supplier Invoice</button>
        </form>
    </div>
</div>
@endsection
