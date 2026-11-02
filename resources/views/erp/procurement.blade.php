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
    .procurement-grid>*{min-width:0}
    .procurement-card{min-width:0}
    #purchase-order-items{width:100%;table-layout:fixed}
    #purchase-order-items input{min-width:0}
    #purchase-order-items th:first-child,#purchase-order-items td:first-child{width:35%}
    @media(max-width:1400px){.procurement-grid{grid-template-columns:repeat(2,minmax(260px,1fr))}}
    @media(max-width:700px){
        .procurement-grid{grid-template-columns:minmax(0,1fr);gap:12px}
        .procurement-card{padding:14px}
        .po-items-wrap{overflow-x:visible}
        #purchase-order-items{display:block;table-layout:auto}
        #purchase-order-items thead{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        #purchase-order-items tbody{display:grid;gap:10px}
        #purchase-order-items tbody tr{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:8px;padding:10px;border:1px solid #dedbd5;border-radius:10px;background:#fff}
        #purchase-order-items tbody td{display:flex;flex-direction:column;justify-content:center;min-width:0;padding:0;border:0}
        #purchase-order-items tbody td:before{content:attr(data-label);margin-bottom:4px;color:#5e5a56;font-size:.66rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
        #purchase-order-items tbody td:first-child{grid-column:1/-1}
        #purchase-order-items tbody td:nth-child(4){align-items:flex-start;text-align:left!important;font-weight:700}
        #purchase-order-items tbody td:last-child{align-items:flex-end;justify-content:flex-end}
        #purchase-order-items tfoot{display:block;margin-top:8px}
        #purchase-order-items tfoot tr{display:flex;justify-content:flex-end;gap:10px}
        #purchase-order-items tfoot th{padding:4px 0}
        #purchase-order-items tfoot th:first-child{flex:1;text-align:right}
        #purchase-order-items tfoot th:last-child{min-width:80px}
    }
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
            <input class="form-control mb-2" name="order_date" type="date" value="{{ old('order_date', now()->format('Y-m-d')) }}">
            <label class="form-label">Items</label>
            <div class="table-responsive po-items-wrap mb-2">
                <table class="table table-sm align-middle" id="purchase-order-items">
                    <thead><tr><th>Description</th><th style="width:70px">Qty</th><th style="width:100px">Unit price</th><th style="width:75px">Tax %</th><th style="width:105px">Total</th><th></th></tr></thead>
                    <tbody>
                        @foreach(old('items', [['description' => '', 'quantity' => 1, 'unit_price' => '']]) as $i => $item)
                            <tr>
                                <td data-label="Description"><input class="form-control form-control-sm" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Item or service" required></td>
                                <td data-label="Quantity"><input class="form-control form-control-sm po-quantity" name="items[{{ $i }}][quantity]" type="number" min="0.001" step="0.001" value="{{ $item['quantity'] ?? 1 }}" required></td>
                                <td data-label="Unit price"><input class="form-control form-control-sm po-unit-price" name="items[{{ $i }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? '' }}" placeholder="0.00" required></td>
                                <td data-label="Tax rate (%)"><input class="form-control form-control-sm po-tax-rate" name="items[{{ $i }}][tax_rate]" type="number" min="0" max="100" step="0.01" value="{{ $item['tax_rate'] ?? 0 }}" placeholder="0"></td>
                                <td data-label="Total including tax" class="text-end po-line-total">0.00</td>
                                <td data-label="Remove"><button type="button" class="btn btn-outline-danger btn-sm remove-po-row" aria-label="Remove item">&times;</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="4" class="text-end">Subtotal</th><th class="text-end" id="purchase-order-subtotal">0.00</th><th></th></tr>
                        <tr><th colspan="4" class="text-end">Tax</th><th class="text-end" id="purchase-order-tax">0.00</th><th></th></tr>
                        <tr><th colspan="4" class="text-end">PO total</th><th class="text-end" id="purchase-order-total">0.00</th><th></th></tr>
                    </tfoot>
                </table>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="add-po-row"><i class="bi bi-plus"></i> Add item</button>
            <label class="form-label">Status</label>
            <select class="form-select mb-2" name="status">
                <option>Draft</option>
                <option>Issued</option>
                <option>Closed</option>
            </select>
            <label class="form-label">Notes</label>
            <textarea class="form-control mb-2" name="notes" placeholder="Notes" rows="3"></textarea>
            <button class="btn btn-outline-success btn-sm">Prepare Purchase Order</button>
        </form>
        <hr>
        <h3 class="h6">Recent Purchase Orders</h3>
        @forelse($purchaseOrders->take(5) as $po)
            <div class="border-top py-2 d-flex justify-content-between gap-2">
                <a href="{{ route('erp.purchase-orders.show', $po) }}">{{ $po->po_number }} · {{ $po->supplier?->name }}</a>
                <span class="text-nowrap">{{ number_format($po->amount, 2) }}</span>
            </div>
        @empty
            <div class="text-muted small">No purchase orders yet.</div>
        @endforelse
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
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('purchase-order-items');
    if (!table) return;
    let index = table.querySelectorAll('tbody tr').length;
    const updateTotal = () => {
        let subtotal = 0;
        let taxTotal = 0;
        table.querySelectorAll('tbody tr').forEach((row) => {
            const quantity = Number(row.querySelector('.po-quantity').value || 0);
            const price = Number(row.querySelector('.po-unit-price').value || 0);
            const lineSubtotal = Math.round(quantity * price * 100) / 100;
            const taxRate = Number(row.querySelector('.po-tax-rate').value || 0);
            const lineTax = Math.round(lineSubtotal * taxRate) / 100;
            const line = lineSubtotal + lineTax;
            row.querySelector('.po-line-total').textContent = line.toFixed(2);
            subtotal += lineSubtotal;
            taxTotal += lineTax;
        });
        document.getElementById('purchase-order-subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('purchase-order-tax').textContent = taxTotal.toFixed(2);
        document.getElementById('purchase-order-total').textContent = (subtotal + taxTotal).toFixed(2);
    };
    table.addEventListener('input', updateTotal);
    document.getElementById('add-po-row').addEventListener('click', () => {
        table.querySelector('tbody').insertAdjacentHTML('beforeend', `<tr>
            <td data-label="Description"><input class="form-control form-control-sm" name="items[${index}][description]" placeholder="Item or service" required></td>
            <td data-label="Quantity"><input class="form-control form-control-sm po-quantity" name="items[${index}][quantity]" type="number" min="0.001" step="0.001" value="1" required></td>
            <td data-label="Unit price"><input class="form-control form-control-sm po-unit-price" name="items[${index}][unit_price]" type="number" min="0" step="0.01" placeholder="0.00" required></td>
            <td data-label="Tax rate (%)"><input class="form-control form-control-sm po-tax-rate" name="items[${index}][tax_rate]" type="number" min="0" max="100" step="0.01" value="0" placeholder="0"></td>
            <td data-label="Total including tax" class="text-end po-line-total">0.00</td>
            <td data-label="Remove"><button type="button" class="btn btn-outline-danger btn-sm remove-po-row" aria-label="Remove item">&times;</button></td>
        </tr>`);
        index++;
        updateTotal();
    });
    table.addEventListener('click', (event) => {
        if (event.target.closest('.remove-po-row') && table.querySelectorAll('tbody tr').length > 1) {
            event.target.closest('tr').remove();
            updateTotal();
        }
    });
    updateTotal();
});
</script>
@endpush
@endsection
