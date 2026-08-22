@extends('layouts.app')
@section('title', 'Retail POS')

@section('content')
@include('retail.partials.nav')

<style>
    .pos-shell{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(320px,.8fr);gap:16px}
    .pos-band{background:#fff;border:1px solid #d9dee8;border-radius:8px;padding:16px}
    .pos-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .pos-kpi{border:1px solid #e1e5ee;border-radius:8px;padding:12px;background:#fbfcfd}
    .pos-kpi span{display:block;color:#667085;font-size:.72rem;font-weight:800;text-transform:uppercase}
    .pos-kpi strong{display:block;font-size:1.2rem;color:#0f766e}
    .pos-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .pos-line{display:grid;grid-template-columns:minmax(220px,2fr) 82px 110px 100px 90px;gap:8px;align-items:center}
    .pos-pay{display:grid;grid-template-columns:minmax(150px,1fr) minmax(150px,1fr) 120px minmax(120px,1fr) minmax(120px,1fr);gap:8px;align-items:center}
    .pos-actions{display:flex;flex-wrap:wrap;gap:8px}
    .pos-list-row{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #edf0f5;padding:9px 0}
    .pos-search-box{position:relative}
    .pos-suggestions{position:absolute;z-index:20;top:calc(100% + 4px);left:0;right:0;display:none;max-height:280px;overflow:auto;border:1px solid #d9dee8;border-radius:8px;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.12)}
    .pos-suggestion{width:100%;border:0;border-bottom:1px solid #edf0f5;background:#fff;padding:10px 12px;text-align:left;display:flex;justify-content:space-between;gap:12px;align-items:center}
    .pos-suggestion:hover,.pos-suggestion:focus{background:#eef8f4;outline:0}
    .pos-suggestion strong{display:block;color:#111827}
    .pos-suggestion span{display:block;color:#667085;font-size:.78rem}
    .pos-suggestion .price{font-weight:800;color:#0f766e;white-space:nowrap}
    @media(max-width:1100px){.pos-shell{grid-template-columns:1fr}.pos-kpis,.pos-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pos-line,.pos-pay{grid-template-columns:1fr 1fr}}
    @media(max-width:640px){.pos-kpis,.pos-grid,.pos-line,.pos-pay{grid-template-columns:1fr}}
</style>

<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Point of Sale</h1>
        <div class="text-muted">Retail POS extends shared orders, payments, inventory, CRM, promotions, loyalty, and audit services.</div>
    </div>
    <div class="pos-actions">
        <a class="btn btn-outline-dark" href="{{ route('retail.products.index') }}"><i class="bi bi-plus-square me-1"></i>Add Product</a>
        <a class="btn btn-outline-dark" href="{{ route('retail.returns.index') }}"><i class="bi bi-arrow-counterclockwise me-1"></i>Returns</a>
        <a class="btn btn-outline-dark" href="{{ route('retail.gift-cards.index') }}"><i class="bi bi-credit-card-2-front me-1"></i>Gift Cards</a>
        <a class="btn btn-success" href="{{ route('pos-orders.index') }}"><i class="bi bi-receipt me-1"></i>Shared POS</a>
    </div>
</div>

<div class="pos-kpis mb-3">
    @foreach(['Sales Today', 'Transactions Today', 'Average Basket Value', 'Net Revenue'] as $label)
        <div class="pos-kpi">
            <span>{{ $label }}</span>
            <strong>{{ number_format((float) ($metrics[$label] ?? 0), str_contains($label, 'Transactions') ? 0 : 2) }}</strong>
        </div>
    @endforeach
</div>

<div class="pos-shell">
    <div class="d-grid gap-3">
        <div class="pos-band">
            <form method="GET" action="{{ route('retail.pos.index') }}" class="pos-grid">
                <select class="form-select" name="identifier_type" id="posIdentifierType">
                    <option value="barcode">Barcode</option>
                    <option value="sku">SKU</option>
                    <option value="qr_product_code">QR Product Code</option>
                    <option value="gtin">GTIN</option>
                    <option value="upc">UPC</option>
                    <option value="ean">EAN</option>
                    <option value="internal_product_number">Internal Product Number</option>
                </select>
                <div class="pos-search-box">
                    <input class="form-control" id="posProductSearch" placeholder="Search product name, SKU, barcode" autocomplete="off" aria-label="Product Search" autofocus>
                    <div class="pos-suggestions" id="posProductSuggestions" role="listbox" aria-label="Product suggestions"></div>
                </div>
                <input class="form-control" name="identifier" id="posIdentifier" placeholder="Scan or enter product code" value="{{ request('identifier') }}">
                <button class="btn btn-success"><i class="bi bi-upc-scan me-1"></i>Scan Product</button>
                <a class="btn btn-outline-dark" href="{{ route('retail.scanning.index') }}"><i class="bi bi-camera me-1"></i>Camera Scan</a>
            </form>
            @if(request()->filled('identifier'))
                <div class="mt-3 p-3 border rounded-2">
                    @if($scanProduct)
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong>{{ $scanProduct->name }}</strong>
                                <div class="small text-muted">{{ $scanProduct->sku }} · Stock {{ number_format((float) $scanProduct->stock_quantity, 3) }}</div>
                            </div>
                            <div class="fw-bold">{{ number_format((float) $scanProduct->price, 2) }}</div>
                        </div>
                    @else
                        <div class="text-danger fw-semibold">Product code was not found or is not available for this business.</div>
                    @endif
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('retail.pos.sales.store') }}" class="d-grid gap-3">
            @csrf
            <div class="pos-band">
                <div class="pos-grid">
                    <select class="form-select" name="client_id">
                        <option value="">Walk-in Customer</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" name="customer_name" placeholder="Customer name">
                    <input class="form-control" name="customer_phone" placeholder="Customer phone">
                    <select class="form-select" name="customer_type">
                        <option>Retail Customer</option>
                        <option>VIP Customer</option>
                        <option>Wholesale Customer</option>
                        <option>Corporate Customer</option>
                    </select>
                    <select class="form-select" name="sale_type" id="retail-pos-sale-type">
                        @foreach($saleTypes as $saleType)
                            <option>{{ $saleType }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="channel">
                        @foreach($channels as $channel)
                            <option>{{ $channel }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="branch_id">
                        <option value="">Store / Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="retail_cash_drawer_id">
                        <option value="">Cash Drawer</option>
                        @foreach($drawers as $drawer)
                            <option value="{{ $drawer->id }}">{{ $drawer->drawer_number }} · {{ $drawer->cashier?->name }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" name="coupon_code" placeholder="Coupon code">
                    <select class="form-select" name="retail_promotion_id">
                        <option value="">Promotion</option>
                        @foreach($promotions as $promotion)
                            <option value="{{ $promotion->id }}">{{ $promotion->name }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" name="layaway_due_at" type="date" title="Layaway due date">
                    <input class="form-control" name="notes" placeholder="Sale notes">
                </div>
            </div>

            <div class="pos-band">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Cart</h2>
                    <span class="status-pill">Discounts, tax, promotions, coupons, exchanges, refunds, layaway</span>
                </div>
                @for($i = 0; $i < 4; $i++)
                    @php
                        $selectedProduct = $i === 0 ? $scanProduct : null;
                        $selectedTaxRate = is_numeric($selectedProduct?->retailProfile?->tax_class) ? $selectedProduct?->retailProfile?->tax_class : '';
                    @endphp
                    <div class="pos-line mb-2">
                        <select class="form-select" name="items[{{ $i }}][product_id]" data-cart-product="{{ $i }}">
                            <option value="">Product / Quick Sale</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected($selectedProduct?->id === $product->id)>{{ $product->name }} · {{ $product->sku }}</option>
                            @endforeach
                        </select>
                        <input class="form-control" name="items[{{ $i }}][quantity]" data-cart-quantity="{{ $i }}" type="number" step="0.001" min="0.001" value="{{ $i === 0 ? 1 : '' }}" placeholder="Qty">
                        <input class="form-control" name="items[{{ $i }}][unit_price]" data-cart-price="{{ $i }}" type="number" step="0.01" min="0" value="{{ $selectedProduct ? (float) $selectedProduct->price : '' }}" placeholder="Price">
                        <input class="form-control" name="items[{{ $i }}][discount]" type="number" step="0.01" min="0" placeholder="Discount">
                        <input class="form-control" name="items[{{ $i }}][tax_rate]" data-cart-tax="{{ $i }}" type="number" step="0.01" min="0" max="100" value="{{ $selectedTaxRate }}" placeholder="Tax %">
                        <input class="form-control" name="items[{{ $i }}][description]" data-cart-description="{{ $i }}" placeholder="Description / SKU note" value="{{ $selectedProduct?->name }}">
                    </div>
                @endfor
            </div>

            <div class="pos-band">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Split Payments</h2>
                    <span class="status-pill">Cash · Card · Mobile Money · Bank Transfer · Wallet · Gift Card · Store Credit</span>
                </div>
                @for($i = 0; $i < 3; $i++)
                    <div class="pos-pay mb-2">
                        <select class="form-select" name="payments[{{ $i }}][method_type]">
                            @foreach($paymentTypes as $type)
                                <option @selected($i === 0 && $type === 'Cash')>{{ $type }}</option>
                            @endforeach
                        </select>
                        <select class="form-select" name="payments[{{ $i }}][payment_method_id]">
                            <option value="">Shared payment method</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                        <input class="form-control" name="payments[{{ $i }}][amount]" type="number" step="0.01" min="0" placeholder="Amount">
                        <input class="form-control" name="payments[{{ $i }}][reference]" placeholder="Reference">
                        <select class="form-select" name="payments[{{ $i }}][retail_gift_card_id]">
                            <option value="">Gift card</option>
                            @foreach($giftCards as $card)
                                <option value="{{ $card->id }}">{{ $card->card_number }} · {{ number_format((float) $card->balance, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endfor
                <div class="pos-actions mt-3">
                    <button class="btn btn-success"><i class="bi bi-cart-check me-1"></i>Complete Sale</button>
                    <button class="btn btn-outline-dark" name="sale_type" value="Layaway"><i class="bi bi-clock-history me-1"></i>Save Layaway</button>
                    <a class="btn btn-outline-dark" href="{{ route('retail.returns.index') }}"><i class="bi bi-arrow-left-right me-1"></i>Return / Exchange</a>
                </div>
            </div>
        </form>
    </div>

    <aside class="d-grid gap-3">
        <div class="pos-band">
            <h2 class="h5 mb-2">Cash Drawer</h2>
            <form method="POST" action="{{ route('retail.pos.drawers.open') }}" class="d-grid gap-2 mb-3">
                @csrf
                <select class="form-select" name="branch_id">
                    <option value="">Store / Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                <input class="form-control" name="drawer_number" placeholder="Register / drawer number" required>
                <input class="form-control" name="opening_float" type="number" step="0.01" min="0" placeholder="Opening float">
                <button class="btn btn-outline-dark"><i class="bi bi-unlock me-1"></i>Open Drawer</button>
            </form>
            @forelse($drawers as $drawer)
                <form method="POST" action="{{ route('retail.pos.drawers.close', $drawer) }}" class="border-top pt-2 mt-2 d-grid gap-2">
                    @csrf
                    <div class="d-flex justify-content-between">
                        <strong>{{ $drawer->drawer_number }}</strong>
                        <span>{{ number_format((float) $drawer->expected_cash, 2) }}</span>
                    </div>
                    <input class="form-control" name="counted_cash" type="number" step="0.01" min="0" placeholder="Counted cash" required>
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-lock me-1"></i>Close Drawer</button>
                </form>
            @empty
                <div class="text-muted">No open drawers.</div>
            @endforelse
        </div>

        <div class="pos-band">
            <h2 class="h5 mb-2">Recent Transactions</h2>
            @forelse($recentOrders as $order)
                <div class="pos-list-row">
                    <div>
                        <strong>{{ $order->order_number }}</strong>
                        <div class="small text-muted">{{ $order->client?->name ?: $order->customer_name ?: 'Walk-in customer' }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">{{ number_format((float) $order->amount_paid, 2) }}</div>
                        @if($order->status !== 'cancelled')
                            <form method="POST" action="{{ route('retail.pos.orders.void', $order) }}">
                                @csrf
                                <button class="btn btn-sm btn-link text-danger p-0">Void</button>
                            </form>
                        @else
                            <span class="small text-muted">Voided</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-muted">No POS transactions yet.</div>
            @endforelse
        </div>

        <div class="pos-band">
            <h2 class="h5 mb-2">Top Products</h2>
            @forelse($topProducts as $product)
                <div class="pos-list-row">
                    <span>{{ $product->title }}</span>
                    <strong>{{ number_format((float) $product->total, 2) }}</strong>
                </div>
            @empty
                <div class="text-muted">Sales mix appears after checkout.</div>
            @endforelse
        </div>

        <div class="pos-band">
            <h2 class="h5 mb-2">Top Cashiers</h2>
            @forelse($topCashiers as $cashier)
                <div class="pos-list-row">
                    <span>{{ $cashier->name }}</span>
                    <strong>{{ number_format((float) $cashier->revenue, 2) }}</strong>
                </div>
            @empty
                <div class="text-muted">Cashier KPIs appear once sales are posted.</div>
            @endforelse
        </div>
    </aside>
</div>
@php
    $posSearchProducts = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'barcode' => $product->retailProfile?->barcode,
        'price' => (float) $product->price,
        'stock' => (float) $product->stock_quantity,
        'tax_rate' => is_numeric($product->retailProfile?->tax_class) ? (float) $product->retailProfile?->tax_class : '',
    ])->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const products = @json($posSearchProducts);
    const search = document.getElementById('posProductSearch');
    const suggestions = document.getElementById('posProductSuggestions');
    const identifier = document.getElementById('posIdentifier');
    const identifierType = document.getElementById('posIdentifierType');

    if (!search || !suggestions || !identifier || !identifierType) return;

    const money = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function productCode(product) {
        return product.barcode || product.sku || String(product.id);
    }

    function selectProduct(product) {
        search.value = `${product.name} (${product.sku || productCode(product)})`;
        identifier.value = productCode(product);
        identifierType.value = product.barcode ? 'barcode' : 'sku';
        suggestions.style.display = 'none';

        const row = 0;
        const productSelect = document.querySelector(`[data-cart-product="${row}"]`);
        const quantity = document.querySelector(`[data-cart-quantity="${row}"]`);
        const price = document.querySelector(`[data-cart-price="${row}"]`);
        const tax = document.querySelector(`[data-cart-tax="${row}"]`);
        const description = document.querySelector(`[data-cart-description="${row}"]`);

        if (productSelect) productSelect.value = product.id;
        if (quantity && !quantity.value) quantity.value = 1;
        if (price) price.value = product.price;
        if (tax) tax.value = product.tax_rate;
        if (description) description.value = product.name;
    }

    function renderSuggestions(matches) {
        suggestions.innerHTML = '';

        if (!matches.length) {
            suggestions.innerHTML = '<div class="p-3 text-muted">No matching products.</div>';
            suggestions.style.display = 'block';
            return;
        }

        matches.forEach((product) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pos-suggestion';
            button.setAttribute('role', 'option');
            const details = document.createElement('span');
            const name = document.createElement('strong');
            const meta = document.createElement('span');
            const price = document.createElement('span');

            name.textContent = product.name;
            meta.textContent = `${product.sku || 'No SKU'}${product.barcode ? ` · ${product.barcode}` : ''} · Stock ${product.stock}`;
            price.className = 'price';
            price.textContent = money.format(product.price);
            details.append(name, meta);
            button.append(details, price);
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                selectProduct(product);
            });
            suggestions.appendChild(button);
        });

        suggestions.style.display = 'block';
    }

    search.addEventListener('input', () => {
        const term = search.value.trim().toLowerCase();
        if (term.length < 1) {
            suggestions.style.display = 'none';
            return;
        }

        const matches = products.filter((product) => [
            product.name,
            product.sku,
            product.barcode,
            String(product.price),
        ].some((value) => String(value || '').toLowerCase().includes(term))).slice(0, 8);

        renderSuggestions(matches);
    });

    search.addEventListener('focus', () => {
        if (search.value.trim()) {
            search.dispatchEvent(new Event('input'));
        }
    });

    document.addEventListener('click', (event) => {
        if (!suggestions.contains(event.target) && event.target !== search) {
            suggestions.style.display = 'none';
        }
    });
});
</script>
@endsection
