@extends('layouts.app')
@section('title', $order->exists ? 'Edit Order' : 'New Order')
@section('content')
@php
    $items = old('items', $order->exists
        ? $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'title' => $item->title,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'discount' => $item->discount,
            'tax_rate' => $item->tax_rate,
        ])->all()
        : [['title'=>'','description'=>'','quantity'=>1,'unit_price'=>0,'discount'=>0,'tax_rate'=>'']]);
    $productOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'description' => $product->description ?: $product->category?->name ?: $product->name,
        'price' => $product->price,
        'stock' => $product->formattedStock(),
    ])->values();
    $action = $order->exists ? route('pos-orders.update', $order) : route('pos-orders.store');
@endphp
<style>
    .pos-shell { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:20px; align-items:start; }
    .pos-panel { background:#1f1f1f; color:#f8fafc; border-radius:8px; overflow:hidden; box-shadow:0 12px 28px rgba(15,23,42,.18); }
    .pos-panel-header { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; background:#242424; border-bottom:1px solid #313131; }
    .pos-action { width:100%; border:0; background:#1f1f1f; color:#b983ff; display:flex; align-items:center; gap:10px; padding:15px 16px; text-align:left; border-bottom:1px solid #2c2c2c; }
    .pos-action:hover { background:#282828; color:#d6b4ff; }
    .pos-action.locked { color:#777; cursor:not-allowed; }
    .pos-action .bi-lock-fill { margin-left:auto; color:#b983ff; }
    .pos-divider { height:7px; background:#101010; }
    .pos-section { display:none; padding:16px; border-bottom:1px solid #2c2c2c; background:#252525; }
    .pos-section.active { display:block; }
    .pos-section label { color:#d1d5db; }
    .pos-section .form-control,.pos-section .form-select { background:#111; color:#fff; border-color:#3f3f46; }
    .pos-section .form-control::placeholder { color:#8b8b8b; }
    .cart-table th { white-space:nowrap; }
    .cart-table input { min-width:90px; }
    .cart-table .item-title { min-width:170px; }
    .cart-table .item-description { min-width:220px; }
    .cart-table .item-actions { min-width:150px; }
    .client-mini { border:1px solid #3f3f46; border-radius:6px; padding:10px; margin-bottom:8px; background:#181818; }
    .product-pick { border:1px solid #3f3f46; border-radius:6px; padding:10px; margin-bottom:8px; background:#181818; }
    .product-pick button { float:right; }
    @media (max-width: 992px) { .pos-shell { grid-template-columns:1fr; } }
</style>

<form method="post" action="{{ $action }}">@csrf @if($order->exists) @method('PUT') @endif
<div class="pos-shell">
    <div>
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">POS order cart</div>
                    <h2 class="h5 mb-0">Products and amounts</h2>
                </div>
                <button type="button" class="btn btn-outline-warning btn-sm" id="cart-add-product"><i class="bi bi-plus"></i> Add row</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle cart-table" id="pos-items-table">
                    <thead><tr><th>Title</th><th>Description</th><th>Qty</th><th>Unit</th><th>Discount</th><th>Tax %</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach($items as $i => $item)
                        <tr>
                            <td>
                                <select class="form-select mb-2 product-select" name="items[{{ $i }}][product_id]">
                                    <option value="">Custom product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}"
                                            data-name="{{ $product->name }}"
                                            data-description="{{ $product->description ?: $product->category?->name ?: $product->name }}"
                                            data-price="{{ $product->price }}"
                                            @selected(($item['product_id'] ?? '') == $product->id)>
                                            {{ $product->name }} - {{ number_format($product->price,2) }} - {{ $product->formattedStock() }}
                                        </option>
                                    @endforeach
                                </select>
                                <input class="form-control item-title" name="items[{{ $i }}][title]" value="{{ $item['title'] ?? '' }}" placeholder="Product">
                            </td>
                            <td><input class="form-control item-description" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Description" required></td>
                            <td><input class="form-control" name="items[{{ $i }}][quantity]" type="number" min="0.001" step="0.001" value="{{ $item['quantity'] ?? 1 }}" required></td>
                            <td><input class="form-control" name="items[{{ $i }}][unit_price]" type="number" step="0.01" value="{{ $item['unit_price'] ?? 0 }}" required></td>
                            <td><input class="form-control" name="items[{{ $i }}][discount]" type="number" step="0.01" value="{{ $item['discount'] ?? 0 }}"></td>
                            <td><input class="form-control tax-field" name="items[{{ $i }}][tax_rate]" type="number" step="0.01" min="0" value="{{ $item['tax_rate'] ?? '' }}" placeholder="Optional"></td>
                            <td class="item-actions">
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-dark btn-sm clear-pos-product">Cancel</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-pos-row">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h5">Payment</h2>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Order date</label><input class="form-control" type="date" name="order_date" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}" required></div>
                <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status">
                    @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'approved' => 'Approved', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $order->status ?: 'pending') === $value)>{{ $label }}</option>
                    @endforeach
                </select></div>
                <div class="col-md-4"><label class="form-label">Amount paid</label><input class="form-control" type="number" step="0.01" name="amount_paid" value="{{ old('amount_paid', $order->amount_paid ?? 0) }}"></div>
                <div class="col-md-6"><label class="form-label">Payment method</label><select class="form-select" name="payment_method_id"><option value="">Select method</option>@foreach($methods as $method)<option value="{{ $method->id }}" @selected(old('payment_method_id', $order->payment_method_id)==$method->id)>{{ $method->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Optional custom amount</label><input class="form-control" type="number" step="0.01" name="custom_amount" id="custom-amount-field" value="{{ old('custom_amount', $order->custom_amount ?? 0) }}"></div>
            </div>
            <button class="btn btn-warning mt-4"><i class="bi bi-check2-circle"></i> {{ $order->exists ? 'Update Order' : 'Create Order' }}</button>
        </div></div>
    </div>

    <aside class="pos-panel">
        <div class="pos-panel-header">
            <a href="{{ route('pos-orders.index') }}" class="text-white text-decoration-none"><i class="bi bi-arrow-left"></i></a>
            <strong>{{ $order->exists ? $order->order_number : 'New order' }}</strong>
            <button class="btn btn-link btn-sm text-warning text-decoration-none p-0">{{ $order->exists ? 'UPDATE' : 'CREATE' }}</button>
        </div>
        <button type="button" class="pos-action" data-panel="products"><i class="bi bi-plus-lg"></i> Add products <i class="bi bi-upc-scan ms-auto"></i></button>
        <div class="pos-section" id="panel-products">
            @forelse($products as $product)
                <div class="product-pick">
                    <button type="button" class="btn btn-warning btn-sm add-catalog-product"
                        data-id="{{ $product->id }}"
                        data-name="{{ e($product->name) }}"
                        data-description="{{ e($product->description ?: $product->category?->name ?: $product->name) }}"
                        data-price="{{ $product->price }}">Add</button>
                    <strong>{{ $product->name }}</strong>
                    <div class="small text-muted">{{ $product->category?->name ?: 'Uncategorized' }} · SKU: {{ $product->sku ?: '-' }}</div>
                    <div>{{ number_format($product->price,2) }} · Stock: {{ $product->formattedStock() }}</div>
                    <div style="clear:both"></div>
                </div>
            @empty
                <p class="text-muted mb-0">No active products yet. Add products from the Products page.</p>
            @endforelse
        </div>
        <button type="button" class="pos-action" id="add-custom-amount"><i class="bi bi-plus-lg"></i> Add custom amount</button>
        <div class="pos-section" id="panel-custom-amount">
            <label class="form-label">Optional custom amount</label>
            <input class="form-control" type="number" step="0.01" id="quick-custom-amount" value="{{ old('custom_amount', $order->custom_amount ?? 0) }}" placeholder="0.00">
            <div class="small text-muted mt-2">This is added to the order total without creating a product row.</div>
        </div>
        <div class="pos-divider"></div>
        <button type="button" class="pos-action justify-content-center" data-panel="tax">Set New Tax Rate</button>
        <div class="pos-section" id="panel-tax">
            <label class="form-label">Apply tax rate to all items</label>
            <div class="input-group"><input class="form-control" id="bulk-tax-rate" type="number" step="0.01" placeholder="0"><button type="button" class="btn btn-warning" id="apply-tax-rate">Apply</button></div>
        </div>
        <div class="pos-divider"></div>
        <button type="button" class="pos-action" data-panel="customer"><i class="bi bi-plus-lg"></i> Add customer details</button>
        <div class="pos-section" id="panel-customer">
            <label class="form-label">Existing client</label>
            <select class="form-select mb-3" name="client_id"><option value="">Walk-in / no client</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id', $order->client_id)==$client->id)>{{ $client->name }}</option>@endforeach</select>
            <div class="client-mini">
                <div class="small text-muted mb-2">Or enter customer details for this order</div>
                <label class="form-label">Customer name</label>
                <input class="form-control mb-3" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" placeholder="Walk-in customer">
                <label class="form-label">Customer phone</label>
                <input class="form-control mb-3" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}">
                <label class="form-label">Customer email</label>
                <input class="form-control mb-3" type="email" name="customer_email" value="{{ old('customer_email', $order->customer_email) }}">
                <label class="form-label">Customer type</label>
                <input class="form-control mb-3" name="customer_type" value="{{ old('customer_type', $order->customer_type) }}" placeholder="Retail, wholesale, online">
                <label class="form-label">Customer address</label>
                <textarea class="form-control" name="customer_address" rows="3" placeholder="Address">{{ old('customer_address', $order->customer_address) }}</textarea>
            </div>
        </div>
        <button type="button" class="pos-action" data-panel="note"><i class="bi bi-plus-lg"></i> Add note</button>
        <div class="pos-section" id="panel-note">
            <textarea class="form-control" name="notes" rows="4" placeholder="Order note">{{ old('notes', $order->notes) }}</textarea>
        </div>
    </aside>
</div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let index = document.querySelectorAll('#pos-items-table tbody tr').length;
    const tbody = document.querySelector('#pos-items-table tbody');
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const products = @json($productOptions);
    const productOptionsHtml = products.map((product) => `<option value="${product.id}" data-name="${escapeHtml(product.name)}" data-description="${escapeHtml(product.description)}" data-price="${product.price}">${escapeHtml(product.name)} - ${Number(product.price).toFixed(2)} - ${escapeHtml(product.stock)}</option>`).join('');
    const addRow = (title = '', description = '', price = 0, productId = '') => {
        tbody.insertAdjacentHTML('beforeend', `<tr>
            <td>
                <select class="form-select mb-2 product-select" name="items[${index}][product_id]">
                    <option value="">Custom product</option>
                    ${productOptionsHtml}
                </select>
                <input class="form-control item-title" name="items[${index}][title]" value="${escapeHtml(title)}" placeholder="Product">
            </td>
            <td><input class="form-control item-description" name="items[${index}][description]" value="${escapeHtml(description)}" placeholder="Description" required></td>
            <td><input class="form-control" name="items[${index}][quantity]" type="number" min="0.001" step="0.001" value="1" required></td>
            <td><input class="form-control" name="items[${index}][unit_price]" type="number" step="0.01" value="${price}" required></td>
            <td><input class="form-control" name="items[${index}][discount]" type="number" step="0.01" value="0"></td>
            <td><input class="form-control tax-field" name="items[${index}][tax_rate]" type="number" step="0.01" min="0" placeholder="Optional"></td>
            <td class="item-actions"><div class="d-flex gap-1"><button type="button" class="btn btn-outline-dark btn-sm clear-pos-product">Cancel</button><button type="button" class="btn btn-outline-danger btn-sm remove-pos-row">Delete</button></div></td>
        </tr>`);
        if (productId) {
            const row = tbody.lastElementChild;
            row.querySelector('.product-select').value = productId;
        }
        index++;
    };

    document.getElementById('cart-add-product').addEventListener('click', () => addRow());
    document.querySelectorAll('.add-catalog-product').forEach((button) => {
        button.addEventListener('click', () => addRow(button.dataset.name, button.dataset.description, button.dataset.price, button.dataset.id));
    });
    document.getElementById('add-custom-amount').addEventListener('click', () => {
        document.getElementById('panel-custom-amount').classList.toggle('active');
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('.remove-pos-row') && document.querySelectorAll('#pos-items-table tbody tr').length > 1) {
            event.target.closest('tr').remove();
        }

        if (event.target.closest('.clear-pos-product')) {
            const row = event.target.closest('tr');
            row.querySelector('.product-select').value = '';
            row.querySelector('.item-title').value = '';
            row.querySelector('.item-description').value = '';
            row.querySelector('input[name$="[unit_price]"]').value = 0;
        }
    });

    document.addEventListener('change', (event) => {
        if (!event.target.classList.contains('product-select')) return;
        const option = event.target.selectedOptions[0];
        const row = event.target.closest('tr');
        if (!option || !option.value) {
            row.querySelector('.item-title').value = '';
            row.querySelector('.item-description').value = '';
            row.querySelector('input[name$="[unit_price]"]').value = 0;
            return;
        }
        row.querySelector('.item-title').value = option.dataset.name || '';
        row.querySelector('.item-description').value = option.dataset.description || option.dataset.name || '';
        row.querySelector('input[name$="[unit_price]"]').value = option.dataset.price || 0;
    });

    document.querySelectorAll('[data-panel]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = document.getElementById(`panel-${button.dataset.panel}`);
            if (panel) panel.classList.toggle('active');
        });
    });

    document.getElementById('apply-tax-rate').addEventListener('click', () => {
        const rate = document.getElementById('bulk-tax-rate').value;
        document.querySelectorAll('.tax-field').forEach((input) => input.value = rate);
    });

    document.getElementById('quick-custom-amount').addEventListener('input', (event) => {
        document.getElementById('custom-amount-field').value = event.target.value;
    });

    document.getElementById('custom-amount-field').addEventListener('input', (event) => {
        document.getElementById('quick-custom-amount').value = event.target.value;
    });
});
</script>
@endpush
@endsection
