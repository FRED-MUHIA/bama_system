@extends('layouts.app')
@section('title', 'Make a Sale')

@section('content')
@include('retail.partials.nav')

<style>
    .pos-shell{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(280px,.7fr);gap:14px}
    .pos-band{background:#fff;border:1px solid #d9dee8;border-radius:8px;padding:14px}
    .pos-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .pos-kpi{border:1px solid #e1e5ee;border-radius:8px;padding:12px;background:#fbfcfd}
    .pos-kpi span{display:block;color:#667085;font-size:.72rem;font-weight:800;text-transform:uppercase}
    .pos-kpi strong{display:block;font-size:1.2rem;color:#0f766e}
    .pos-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .pos-scan-grid{display:grid;grid-template-columns:minmax(220px,1.35fr) minmax(160px,.85fr) auto auto auto;gap:10px;align-items:center}
    .pos-sell-grid{display:grid;grid-template-columns:minmax(180px,1.1fr) minmax(150px,.9fr) minmax(150px,.9fr);gap:10px}
    .pos-line-item{border:1px solid #edf0f5;border-radius:8px;padding:10px;background:#fbfcfd}
    .pos-line{display:grid;grid-template-columns:minmax(220px,2fr) 88px 120px 120px;gap:8px;align-items:center}
    .pos-line-total{min-height:38px;display:flex;align-items:center;justify-content:flex-end;border:1px solid #e1e5ee;border-radius:6px;padding:0 .75rem;background:#fff;color:#0f766e;font-weight:800}
    .pos-pay{display:grid;grid-template-columns:minmax(150px,.7fr) minmax(160px,1fr);gap:8px;align-items:center}
    .pos-expander[hidden]{display:none!important}
    .pos-actions{display:flex;flex-wrap:wrap;gap:8px}
    .pos-list-row{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #edf0f5;padding:9px 0}
    .pos-list-row:last-child{border-bottom:0}
    .pos-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .pos-summary-tile{border:1px solid #edf0f5;border-radius:8px;background:#fbfcfd;padding:10px}
    .pos-summary-tile span{display:block;color:#667085;font-size:.68rem;font-weight:800;text-transform:uppercase}
    .pos-summary-tile strong{display:block;color:#0f766e;font-size:1.05rem}
    .pos-search-box{position:relative}
    .pos-suggestions{position:absolute;z-index:20;top:calc(100% + 4px);left:0;right:0;display:none;max-height:360px;overflow:auto;border:1px solid #d9dee8;border-radius:8px;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.12)}
    .pos-suggestion{width:100%;border:0;border-bottom:1px solid #edf0f5;background:#fff;padding:10px 12px;text-align:left;display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
    .pos-suggestion:hover,.pos-suggestion:focus{background:#eef8f4;outline:0}
    .pos-suggestion strong{display:block;color:#111827}
    .pos-suggestion-main{min-width:0}
    .pos-suggestion-meta{display:flex;flex-wrap:wrap;gap:2px 10px;color:#667085;font-size:.76rem;margin-top:2px}
    .pos-suggestion-summary{color:#344054;font-size:.75rem;line-height:1.35;margin-top:5px}
    .pos-suggestion-tags{display:flex;flex-wrap:wrap;gap:4px;margin-top:6px}
    .pos-suggestion-tag{border:1px solid #cfeee0;border-radius:999px;background:#f3fbf7;color:#0f5132;font-size:.68rem;font-weight:750;line-height:1.2;padding:2px 7px;white-space:nowrap}
    .pos-suggestion .price{font-weight:800;color:#0f766e;white-space:nowrap;text-align:right}
    .pos-suggestion .price small{display:block;color:#667085;font-size:.68rem;font-weight:650}
    @media(max-width:1100px){.pos-shell{grid-template-columns:1fr}.pos-kpis,.pos-grid,.pos-scan-grid,.pos-sell-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pos-line,.pos-pay{grid-template-columns:1fr 1fr}}
    @media(max-width:720px){.pos-suggestion{flex-direction:column}.pos-suggestion .price{text-align:left}}
    @media(max-width:640px){.pos-kpis,.pos-grid,.pos-scan-grid,.pos-sell-grid,.pos-line,.pos-pay,.pos-summary{grid-template-columns:1fr}.pos-line-total{justify-content:flex-start}}
</style>

<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Make a Sale</h1>
        <div class="text-muted">A simple counter screen for small retail sales.</div>
    </div>
    <div class="pos-actions">
        <a class="btn btn-success" href="{{ route('retail.products.index') }}#retail-add-product"><i class="bi bi-plus-square me-1"></i>Add Product</a>
        <a class="btn btn-outline-dark" href="{{ route('retail.products.index') }}"><i class="bi bi-box-seam me-1"></i>Products</a>
        <a class="btn btn-outline-dark" href="{{ route('retail.returns.index') }}"><i class="bi bi-arrow-counterclockwise me-1"></i>Returns</a>
    </div>
</div>

<div class="pos-shell">
    <div class="d-grid gap-3">
        <div class="pos-band">
            <form method="GET" action="{{ route('retail.pos.index') }}" class="pos-scan-grid">
                <div class="pos-search-box">
                    <input class="form-control" id="posProductSearch" placeholder="Search product name, SKU, barcode" autocomplete="off" aria-label="Product Search" autofocus>
                    <div class="pos-suggestions" id="posProductSuggestions" role="listbox" aria-label="Product suggestions"></div>
                </div>
                <input type="hidden" name="identifier_type" id="posIdentifierType" value="{{ request('identifier_type', 'barcode') }}">
                <input class="form-control" name="identifier" id="posIdentifier" placeholder="Scan barcode or SKU" value="{{ request('identifier') }}">
                <button class="btn btn-success" type="button" id="posAddSaleButton"><i class="bi bi-cart-plus me-1"></i>Sale</button>
                <button class="btn btn-success" type="submit"><i class="bi bi-upc-scan me-1"></i>Scan Product</button>
                <a class="btn btn-outline-dark" href="{{ route('retail.scanning.index') }}" title="Camera scan" aria-label="Camera scan"><i class="bi bi-camera"></i></a>
            </form>
            <div class="small mt-2" id="posSaleFeedback" aria-live="polite"></div>
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
                <div class="pos-sell-grid">
                    <select class="form-select" name="client_id">
                        <option value="">Walk-in Customer</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" name="customer_name" placeholder="Customer name">
                    <select class="form-select" name="branch_id">
                        <option value="">Store / Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="sale_type" value="Sale">
                <input type="hidden" name="channel" value="Store">
                <input type="hidden" name="customer_type" value="Retail Customer">
                <div class="pos-expander mt-3" id="saleOptions" hidden>
                    <div class="pos-grid">
                        <input class="form-control" name="customer_phone" placeholder="Customer phone">
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
                <button class="btn btn-sm btn-outline-dark mt-3" type="button" data-pos-toggle="saleOptions" aria-expanded="false" aria-controls="saleOptions">
                    <i class="bi bi-sliders me-1"></i>More sale options
                </button>
            </div>

            <div class="pos-band">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Cart</h2>
                    <button class="btn btn-sm btn-outline-dark" type="button" data-pos-toggle="extraCartLines" aria-expanded="false" aria-controls="extraCartLines">
                        <i class="bi bi-plus-lg me-1"></i>Add Item
                    </button>
                </div>
                @for($i = 0; $i < 3; $i++)
                    @if($i === 1)
                        <div class="pos-expander" id="extraCartLines" hidden>
                    @endif
                    @php
                        $selectedProduct = $i === 0 ? $scanProduct : null;
                        $selectedVariant = $selectedProduct?->variantProfile;
                        $selectedTaxRate = is_numeric($selectedProduct?->retailProfile?->tax_class) ? $selectedProduct?->retailProfile?->tax_class : '';
                    @endphp
                    <div class="pos-line-item mb-2">
                        <div class="pos-line">
                            <select class="form-select" name="items[{{ $i }}][product_id]" data-cart-product="{{ $i }}">
                                <option value="">Select product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected($selectedProduct?->id === $product->id)>{{ $product->name }} · {{ $product->sku }}</option>
                                @endforeach
                            </select>
                            <input class="form-control" name="items[{{ $i }}][quantity]" data-cart-quantity="{{ $i }}" type="number" step="0.001" min="0.001" value="{{ $i === 0 ? 1 : '' }}" placeholder="Qty">
                            <input class="form-control" name="items[{{ $i }}][unit_price]" data-cart-price="{{ $i }}" type="number" step="0.01" min="0" value="{{ $selectedProduct ? (float) $selectedProduct->price : '' }}" placeholder="Price">
                            <div class="pos-line-total" data-cart-line-total="{{ $i }}">0.00</div>
                        </div>
                        <input type="hidden" name="items[{{ $i }}][retail_product_variant_id]" data-cart-variant="{{ $i }}" value="{{ $selectedVariant?->id }}">
                        <input type="hidden" name="items[{{ $i }}][description]" data-cart-description="{{ $i }}" value="{{ $selectedProduct?->name }}">
                        <input type="hidden" name="items[{{ $i }}][discount]" data-cart-discount="{{ $i }}">
                        <input type="hidden" name="items[{{ $i }}][tax_rate]" data-cart-tax="{{ $i }}" value="{{ $selectedTaxRate }}">
                    </div>
                    @if($i === 2)
                        </div>
                    @endif
                @endfor
            </div>

            <div class="pos-band">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Payment</h2>
                    <button class="btn btn-sm btn-outline-dark" type="button" data-pos-toggle="paymentOptions" aria-expanded="false" aria-controls="paymentOptions">
                        <i class="bi bi-sliders me-1"></i>More
                    </button>
                </div>
                <div class="pos-pay mb-2">
                    <select class="form-select" name="payments[0][method_type]">
                        @foreach($paymentTypes as $type)
                            <option @selected($type === 'Cash')>{{ $type }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" name="payments[0][amount]" type="number" step="0.01" min="0" placeholder="Amount">
                </div>
                <div class="pos-expander" id="paymentOptions" hidden>
                    <div class="small text-muted fw-bold text-uppercase mb-2">Split Payments</div>
                    @for($i = 1; $i < 3; $i++)
                        <div class="pos-pay mb-2">
                            <select class="form-select" name="payments[{{ $i }}][method_type]">
                                @foreach($paymentTypes as $type)
                                    <option>{{ $type }}</option>
                                @endforeach
                            </select>
                            <input class="form-control" name="payments[{{ $i }}][amount]" type="number" step="0.01" min="0" placeholder="Amount">
                        </div>
                    @endfor
                    <div class="pos-grid mt-3">
                        <input class="form-control" name="payments[0][reference]" placeholder="Payment reference">
                        <select class="form-select" name="payments[0][payment_method_id]">
                            <option value="">Shared payment method</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                        <select class="form-select" name="payments[0][retail_gift_card_id]">
                            <option value="">Gift card</option>
                            @foreach($giftCards as $card)
                                <option value="{{ $card->id }}">{{ $card->card_number }} · {{ number_format((float) $card->balance, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pos-actions mt-3">
                        <button class="btn btn-outline-dark" type="submit" name="sale_type" value="Layaway"><i class="bi bi-clock-history me-1"></i>Save Layaway</button>
                        <a class="btn btn-outline-dark" href="{{ route('retail.returns.index') }}"><i class="bi bi-arrow-left-right me-1"></i>Return / Exchange</a>
                    </div>
                </div>
                <div class="pos-actions mt-3">
                    <button class="btn btn-success" type="submit"><i class="bi bi-cart-check me-1"></i>Complete Sale</button>
                </div>
            </div>
        </form>
    </div>

    <aside class="d-grid gap-3">
        <div class="pos-band">
            <h2 class="h5 mb-2">Today's Summary</h2>
            <div class="pos-summary">
                @foreach($metrics as $label => $value)
                    <div class="pos-summary-tile">
                        <span>{{ $label }}</span>
                        <strong>{{ number_format((float) $value, str_contains($label, 'Transactions') || str_contains($label, 'Stock Alerts') ? 0 : 2) }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

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
            <a class="btn btn-sm btn-outline-dark mb-2" href="{{ route('retail.transactions.index') }}">More · All transactions</a>
            @include('retail.partials.transaction-search')
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
            <h2 class="h5 mb-2">Low Stock</h2>
            @forelse($lowStockProducts as $product)
                <div class="pos-list-row">
                    <span>{{ $product->name }}</span>
                    <strong>{{ $product->formattedStock() }}</strong>
                </div>
            @empty
                <div class="text-muted">Stock alerts will appear here.</div>
            @endforelse
        </div>
    </aside>
</div>
@php
    $posAttributePairs = function (?array $attributes) {
        return collect($attributes ?? [])
            ->reject(fn ($value) => $value === null || $value === '' || (is_array($value) && empty($value)))
            ->map(function ($value, $key) {
                $label = str((string) $key)->replace(['_', '-'], ' ')->headline()->toString();

                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif (is_array($value)) {
                    $value = collect($value)->flatten()->filter(fn ($item) => filled($item))->implode(', ');
                }

                return ['label' => $label, 'value' => (string) $value];
            })
            ->filter(fn ($attribute) => filled($attribute['value']))
            ->values();
    };

    $posAssignmentValue = function ($assignment) {
        $metadata = (array) ($assignment->metadata ?? []);

        return data_get($metadata, 'value')
            ?? data_get($metadata, 'selected_value')
            ?? data_get($metadata, 'selected')
            ?? data_get($metadata, 'option')
            ?? data_get($metadata, 'label')
            ?? data_get($metadata, 'values');
    };

    $posProductAttributes = function ($product) use ($posAttributePairs, $posAssignmentValue) {
        return $posAttributePairs($product->retailProfile?->attributes)
            ->merge($product->attributeAssignments->map(function ($assignment) use ($posAssignmentValue) {
                $value = $posAssignmentValue($assignment);

                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif (is_array($value)) {
                    $value = collect($value)->flatten()->filter(fn ($item) => filled($item))->implode(', ');
                }

                return [
                    'label' => $assignment->attribute?->name,
                    'value' => filled($value) ? (string) $value : null,
                ];
            })->filter(fn ($attribute) => filled($attribute['label'])))
            ->unique(fn ($attribute) => $attribute['label'].'|'.$attribute['value'])
            ->values();
    };

    $posVariantAttributes = function ($variant) use ($posAttributePairs) {
        return $posAttributePairs($variant->attributes)
            ->merge($variant->attributeValueLinks->map(fn ($link) => [
                'label' => $link->attribute?->name ?: $link->value?->attribute?->name,
                'value' => $link->value?->value,
            ])->filter(fn ($attribute) => filled($attribute['label']) && filled($attribute['value'])))
            ->unique(fn ($attribute) => $attribute['label'].'|'.$attribute['value'])
            ->values();
    };

    $posSearchProducts = $products
        ->reject(fn ($product) => $product->variantProfile)
        ->flatMap(function ($product) use ($posProductAttributes, $posVariantAttributes) {
            $baseAttributes = $posProductAttributes($product);
            $variantSummaries = $product->retailVariants
                ->filter(fn ($variant) => $variant->is_active ?? true)
                ->take(4)
                ->map(fn ($variant) => [
                    'name' => $variant->displayName(),
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'attributes' => $posVariantAttributes($variant)->all(),
                ])
                ->values()
                ->all();

            $baseProduct = [
                'id' => $product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->retailProfile?->barcode ?: $product->barcode,
                'brand' => $product->brand?->name ?: $product->retailProfile?->brand,
                'category' => $product->category?->name,
                'type' => $product->retailProfile?->product_type ?: $product->productTypeLabel(),
                'description' => $product->description,
                'price' => (float) $product->price,
                'stock' => (float) $product->stock_quantity,
                'stock_label' => $product->formattedStock(),
                'tax_rate' => is_numeric($product->retailProfile?->tax_class) ? (float) $product->retailProfile?->tax_class : '',
                'attributes' => $baseAttributes->all(),
                'variants' => $variantSummaries,
            ];

            $variantProducts = $product->retailVariants
                ->filter(fn ($variant) => $variant->is_active ?? true)
                ->map(function ($variant) use ($product, $baseAttributes, $posVariantAttributes) {
                    $variantProduct = $variant->product;
                    $variantName = $variant->displayName();
                    $variantAttributes = $posVariantAttributes($variant);
                    $taxClass = $variant->tax_class ?: $variantProduct?->retailProfile?->tax_class ?: $product->retailProfile?->tax_class;

                    return [
                        'id' => $variantProduct?->id ?: $variant->product_id,
                        'parent_id' => $product->id,
                        'variant_id' => $variant->id,
                        'variant_name' => $variantName,
                        'name' => trim($product->name.' - '.$variantName, ' -'),
                        'sku' => $variant->sku ?: $variantProduct?->sku,
                        'barcode' => $variant->barcode ?: $variantProduct?->barcode,
                        'brand' => $product->brand?->name ?: $product->retailProfile?->brand,
                        'category' => $product->category?->name,
                        'type' => 'Variant',
                        'description' => $variantProduct?->description ?: $product->description,
                        'price' => (float) ($variant->retail_price ?? $variantProduct?->price ?? $product->price),
                        'stock' => (float) ($variantProduct?->stock_quantity ?? 0),
                        'stock_label' => $variantProduct?->formattedStock() ?: number_format((float) ($variantProduct?->stock_quantity ?? 0), 3),
                        'tax_rate' => is_numeric($taxClass) ? (float) $taxClass : '',
                        'attributes' => $baseAttributes->merge($variantAttributes)
                            ->unique(fn ($attribute) => $attribute['label'].'|'.$attribute['value'])
                            ->values()
                            ->all(),
                        'variants' => [],
                    ];
                })
                ->values();

            return $variantProducts->prepend($baseProduct);
        })
        ->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const products = @json($posSearchProducts);
    const productsById = new Map(products.map((product) => [String(product.id), product]));
    const search = document.getElementById('posProductSearch');
    const suggestions = document.getElementById('posProductSuggestions');
    const identifier = document.getElementById('posIdentifier');
    const identifierType = document.getElementById('posIdentifierType');
    const saleButton = document.getElementById('posAddSaleButton');
    const saleFeedback = document.getElementById('posSaleFeedback');
    const extraCartLines = document.getElementById('extraCartLines');

    if (!search || !suggestions || !identifier || !identifierType) return;

    const money = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let selectedSearchProduct = null;

    function normalize(value) {
        return String(value || '').trim().toLowerCase();
    }

    function productSearchValues(product) {
        return [
            product.name,
            product.sku,
            product.barcode,
            product.brand,
            product.category,
            product.type,
            product.description,
            String(product.price),
            ...(product.attributes || []).flatMap((attribute) => [attribute.label, attribute.value]),
            ...(product.variants || []).flatMap((variant) => [
                variant.name,
                variant.sku,
                variant.barcode,
                ...(variant.attributes || []).flatMap((attribute) => [attribute.label, attribute.value]),
            ]),
        ].filter(Boolean);
    }

    function productMatchesTerm(product, term) {
        const normalizedTerm = normalize(term);
        if (!normalizedTerm) return false;

        const haystack = productSearchValues(product).map(normalize).join(' ');
        const cleanTokens = normalizedTerm
            .replace(/[()]/g, ' ')
            .split(/\s+/)
            .filter(Boolean);

        return haystack.includes(normalizedTerm) || cleanTokens.every((token) => haystack.includes(token));
    }

    function exactProductMatch(product, term) {
        const normalizedTerm = normalize(term);
        if (!normalizedTerm) return false;

        return [
            product.name,
            product.sku,
            product.barcode,
            `${product.name} (${product.sku || productCode(product)})`,
        ].some((value) => normalize(value) === normalizedTerm);
    }

    function setSaleFeedback(message, tone = 'text-success') {
        if (!saleFeedback) return;

        saleFeedback.className = `small mt-2 ${tone}`;
        saleFeedback.textContent = message;
    }

    function productCode(product) {
        return product.barcode || product.sku || String(product.id);
    }

    function setPanelOpen(panel, open) {
        if (!panel) return;

        panel.hidden = !open;
        document.querySelectorAll(`[data-pos-toggle="${panel.id}"]`).forEach((button) => {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    function showPanel(panel) {
        setPanelOpen(panel, true);
    }

    document.querySelectorAll('[data-pos-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const panel = document.getElementById(button.dataset.posToggle);
            setPanelOpen(panel, panel?.hidden ?? true);
        });
    });

    function cartRows() {
        return Array.from(document.querySelectorAll('[data-cart-product]')).map((select) => select.dataset.cartProduct);
    }

    function revealCartRow(row) {
        if (Number(row) <= 0 || !extraCartLines) return;

        showPanel(extraCartLines);
    }

    function cartRowHasProduct(row, product) {
        const productSelect = document.querySelector(`[data-cart-product="${row}"]`);
        const variant = document.querySelector(`[data-cart-variant="${row}"]`);

        return productSelect?.value === String(product.id)
            && (variant?.value || '') === String(product.variant_id || '');
    }

    function cartRowIsEmpty(row) {
        const productSelect = document.querySelector(`[data-cart-product="${row}"]`);
        const price = document.querySelector(`[data-cart-price="${row}"]`);
        const description = document.querySelector(`[data-cart-description="${row}"]`);

        return !productSelect?.value && !price?.value && !description?.value;
    }

    function updateLineTotal(row) {
        const quantity = document.querySelector(`[data-cart-quantity="${row}"]`);
        const price = document.querySelector(`[data-cart-price="${row}"]`);
        const discount = document.querySelector(`[data-cart-discount="${row}"]`);
        const tax = document.querySelector(`[data-cart-tax="${row}"]`);
        const total = document.querySelector(`[data-cart-line-total="${row}"]`);

        if (!total) return;

        const quantityValue = Number.parseFloat(quantity?.value || '0');
        const priceValue = Number.parseFloat(price?.value || '0');
        const discountValue = Number.parseFloat(discount?.value || '0');
        const taxValue = Number.parseFloat(tax?.value || '0');
        const subtotal = Math.max(quantityValue * priceValue - discountValue, 0);
        const lineTotal = subtotal + (subtotal * taxValue / 100);

        total.textContent = money.format(lineTotal || 0);
    }

    function applyProductToRow(product, row = 0) {
        const productSelect = document.querySelector(`[data-cart-product="${row}"]`);
        const quantity = document.querySelector(`[data-cart-quantity="${row}"]`);
        const price = document.querySelector(`[data-cart-price="${row}"]`);
        const tax = document.querySelector(`[data-cart-tax="${row}"]`);
        const variant = document.querySelector(`[data-cart-variant="${row}"]`);
        const description = document.querySelector(`[data-cart-description="${row}"]`);

        if (productSelect) productSelect.value = product.id;
        if (quantity && !quantity.value) quantity.value = 1;
        if (price) price.value = product.price;
        if (tax) tax.value = product.tax_rate;
        if (variant) variant.value = product.variant_id || '';
        if (description) description.value = product.name;
        updateLineTotal(row);
    }

    function addProductToCart(product) {
        const rows = cartRows();
        const existingRow = rows.find((row) => cartRowHasProduct(row, product));

        if (existingRow !== undefined) {
            const quantity = document.querySelector(`[data-cart-quantity="${existingRow}"]`);
            quantity.value = String((Number.parseFloat(quantity.value || '0') || 0) + 1);
            updateLineTotal(existingRow);
            revealCartRow(existingRow);
            setSaleFeedback(`Added another ${product.name}.`);
            return true;
        }

        const emptyRow = rows.find((row) => cartRowIsEmpty(row));

        if (emptyRow === undefined) {
            setSaleFeedback('All cart rows are full.', 'text-danger');
            return false;
        }

        revealCartRow(emptyRow);
        applyProductToRow(product, emptyRow);
        setSaleFeedback(`${product.name} added to cart.`);
        return true;
    }

    function productFromSearch() {
        const searchTerm = search.value.trim();
        const identifierTerm = identifier.value.trim();

        if (selectedSearchProduct && (
            exactProductMatch(selectedSearchProduct, searchTerm)
            || exactProductMatch(selectedSearchProduct, identifierTerm)
        )) {
            return selectedSearchProduct;
        }

        return products.find((product) => exactProductMatch(product, identifierTerm))
            || products.find((product) => exactProductMatch(product, searchTerm))
            || products.find((product) => productMatchesTerm(product, searchTerm))
            || products.find((product) => productMatchesTerm(product, identifierTerm));
    }

    function selectProduct(product) {
        selectedSearchProduct = product;
        search.value = `${product.name} (${product.sku || productCode(product)})`;
        identifier.value = productCode(product);
        identifierType.value = product.barcode ? 'barcode' : 'sku';
        suggestions.style.display = 'none';
        addProductToCart(product);
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
            const summary = document.createElement('span');
            const tags = document.createElement('span');
            const price = document.createElement('span');
            const attributes = (product.attributes || []).filter((attribute) => attribute.label || attribute.value);
            const variants = (product.variants || []).filter((variant) => variant.name || variant.sku || variant.barcode);
            const visibleAttributes = attributes.slice(0, 5);
            const hiddenAttributeCount = Math.max(attributes.length - visibleAttributes.length, 0);
            const variantText = variants.slice(0, 2).map((variant) => variant.name || variant.sku || variant.barcode).filter(Boolean).join(' / ');

            details.className = 'pos-suggestion-main';
            name.textContent = product.name;
            meta.className = 'pos-suggestion-meta';
            meta.textContent = [
                product.brand ? `Brand: ${product.brand}` : null,
                product.sku ? `SKU: ${product.sku}` : 'No SKU',
                product.barcode ? `Barcode: ${product.barcode}` : null,
                product.category ? `Category: ${product.category}` : null,
                product.type,
            ].filter(Boolean).join(' · ');

            summary.className = 'pos-suggestion-summary';
            summary.textContent = [
                product.description,
                variantText ? `Variants: ${variantText}` : null,
            ].filter(Boolean).join(' · ');

            tags.className = 'pos-suggestion-tags';
            visibleAttributes.forEach((attribute) => {
                const tag = document.createElement('span');
                tag.className = 'pos-suggestion-tag';
                tag.textContent = attribute.value ? `${attribute.label}: ${attribute.value}` : attribute.label;
                tags.appendChild(tag);
            });

            if (hiddenAttributeCount > 0) {
                const tag = document.createElement('span');
                tag.className = 'pos-suggestion-tag';
                tag.textContent = `+${hiddenAttributeCount} more`;
                tags.appendChild(tag);
            }

            price.className = 'price';
            price.textContent = money.format(product.price);
            const stock = document.createElement('small');
            stock.textContent = product.stock_label || `Stock ${product.stock}`;
            price.appendChild(stock);
            details.append(name, meta);
            if (summary.textContent) details.appendChild(summary);
            if (tags.children.length) details.appendChild(tags);
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
        selectedSearchProduct = null;
        const term = search.value.trim().toLowerCase();
        if (term.length < 1) {
            suggestions.style.display = 'none';
            return;
        }

        const matches = products.filter((product) => productMatchesTerm(product, term)).slice(0, 8);

        renderSuggestions(matches);
    });

    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            saleButton?.click();
        }
    });

    saleButton?.addEventListener('click', () => {
        const product = productFromSearch();

        if (!product) {
            setSaleFeedback('No matching product found.', 'text-danger');
            search.dispatchEvent(new Event('input'));
            return;
        }

        selectedSearchProduct = product;
        search.value = `${product.name} (${product.sku || productCode(product)})`;
        identifier.value = productCode(product);
        identifierType.value = product.barcode ? 'barcode' : 'sku';
        suggestions.style.display = 'none';
        addProductToCart(product);
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

    document.querySelectorAll('[data-cart-product]').forEach((select) => {
        select.addEventListener('change', () => {
            const product = productsById.get(select.value);
            if (product) applyProductToRow(product, select.dataset.cartProduct);
        });
    });

    document.querySelectorAll('[data-cart-quantity], [data-cart-price], [data-cart-discount], [data-cart-tax]').forEach((input) => {
        input.addEventListener('input', () => updateLineTotal(input.dataset.cartQuantity || input.dataset.cartPrice || input.dataset.cartDiscount || input.dataset.cartTax));
    });

    document.querySelectorAll('[data-cart-line-total]').forEach((total) => updateLineTotal(total.dataset.cartLineTotal));
});
</script>
@endsection
