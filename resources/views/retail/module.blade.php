@extends('layouts.app')
@section('title', $title)

@section('content')
@include('retail.partials.nav')

<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $title }}</h1>
        <div class="text-muted">Retail extension data is scoped to the active tenant and business.</div>
    </div>
    @if($section === 'products')
        <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#retail-add-product">
            <i class="bi bi-plus-lg me-1"></i>Add Product
        </button>
    @endif
</div>

@if($section === 'products')
    <div class="collapse mb-3" id="retail-add-product">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Add Product</h2>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-dark" href="{{ route('products.export', ['format' => 'csv']) }}"><i class="bi bi-download me-1"></i>CSV</a>
                    <a class="btn btn-sm btn-outline-dark" href="{{ route('products.export', ['format' => 'xls']) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>
                    <a class="btn btn-sm btn-outline-dark" href="{{ route('products.index') }}">Open Full Catalog</a>
                </div>
            </div>
            <form method="POST" action="{{ route('products.store') }}" class="row g-2">
                @csrf
                @include('products.partials.fields', ['product' => $product])
                <div class="col-12">
                    <button class="btn btn-success"><i class="bi bi-save me-1"></i>Save Product</button>
                </div>
            </form>
            <div class="border-top mt-3 pt-3">
                <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="row g-2 align-items-center">
                    @csrf
                    <div class="col-md-8"><input class="form-control" type="file" name="product_file" accept=".csv,.txt,.tsv,.xls,.xlsx" required></div>
                    <div class="col-md-4"><button class="btn btn-outline-dark w-100"><i class="bi bi-upload me-1"></i>Upload CSV / Excel</button></div>
                </form>
                <div class="small text-muted mt-2">Import updates products by SKU, creates missing categories, and posts stock changes through Inventory Core.</div>
            </div>
        </div>
    </div>
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.products.profile') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><select class="form-select" name="product_id" required><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input class="form-control" name="barcode" placeholder="Barcode"></div>
            <div class="col-md-2"><input class="form-control" name="brand" placeholder="Brand"></div>
            <div class="col-md-2"><select class="form-select" name="product_type"><option>Physical Product</option><option>Digital Product</option><option>Service Product</option><option>Gift Card</option><option>Bundle</option><option>Kit</option></select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Active</option><option>Inactive</option><option>Discontinued</option></select></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
    </div>
@elseif($section === 'inventory')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.inventory.adjust') }}" class="row g-2">
            @csrf
            <div class="col-md-4"><select class="form-select" name="product_id" required><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input class="form-control" name="quantity" type="number" step="0.001" placeholder="Qty" required></div>
            <div class="col-md-3"><select class="form-select" name="bucket"><option value="available_stock">Available</option><option value="reserved_stock">Reserved</option><option value="in_transit_stock">In Transit</option><option value="damaged_stock">Damaged</option></select></div>
            <div class="col-md-2"><input class="form-control" name="reference" placeholder="Reference"></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.inventory.reserve') }}" class="row g-2">
                @csrf
                <div class="col-md-4"><select class="form-select" name="product_id" required><option value="">Reserve product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="quantity" type="number" step="0.001" placeholder="Qty" required></div>
                <div class="col-md-2"><input class="form-control" name="branch_id" placeholder="Branch ID"></div>
                <div class="col-md-3"><input class="form-control" name="reference" placeholder="Reservation reference"></div>
                <div class="col-md-1"><button class="btn btn-outline-dark w-100"><i class="bi bi-lock"></i></button></div>
            </form>
        </div>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.inventory.transfer') }}" class="row g-2">
                @csrf
                <div class="col-md-4"><select class="form-select" name="product_id" required><option value="">Transfer product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="quantity" type="number" step="0.001" placeholder="Qty" required></div>
                <div class="col-md-2"><input class="form-control" name="from_branch_id" placeholder="From branch ID"></div>
                <div class="col-md-2"><input class="form-control" name="to_branch_id" placeholder="To branch ID"></div>
                <div class="col-md-2"><button class="btn btn-outline-dark w-100">Transfer</button></div>
            </form>
        </div>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.inventory.replenishment') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" name="product_id" required><option value="">Forecast product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="supplier_id"><option value="">Supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="branch_id"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><input class="form-control" name="forecast_period_days" type="number" value="30" placeholder="Days"></div>
                <div class="col-md-1"><input class="form-control" name="lead_time_days" type="number" value="7" placeholder="Lead"></div>
                <div class="col-md-1"><input class="form-control" name="safety_stock_factor" type="number" step="0.1" value="1.5" placeholder="Safety"></div>
                <div class="col-md-2"><button class="btn btn-outline-dark w-100">Replenish</button></div>
            </form>
        </div>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.inventory.cycle-counts.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" name="product_id" required><option value="">Cycle count product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="retail_warehouse_bin_id"><option value="">Bin / shelf</option>@foreach($bins as $bin)<option value="{{ $bin->id }}">{{ $bin->warehouse?->name }} / {{ $bin->bin_code }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="counted_quantity" type="number" step="0.001" placeholder="Counted qty" required></div>
                <div class="col-md-3"><input class="form-control" name="notes" placeholder="Count notes"></div>
                <div class="col-md-2"><button class="btn btn-outline-dark w-100">Post Count</button></div>
            </form>
        </div>
        <div class="row g-3 border-top mt-3 pt-3">
            <div class="col-lg-6">
                <h3 class="h6">Replenishment Plans</h3>
                @forelse($replenishmentPlans as $plan)
                    <div class="d-flex justify-content-between gap-2 border-bottom py-2">
                        <div><strong>{{ $plan->product?->name }}</strong><div class="small text-muted">Forecast {{ $plan->demand_forecast_qty }} · Safety {{ $plan->safety_stock_qty }} · Reorder {{ $plan->recommended_order_qty }}</div></div>
                        @if(!$plan->purchase_order_id && (float) $plan->recommended_order_qty > 0)
                            <form method="POST" action="{{ route('retail.inventory.replenishment.purchase-order', $plan) }}">@csrf<button class="btn btn-sm btn-outline-success">Draft PO</button></form>
                        @else
                            <span class="status-pill">{{ $plan->status }}</span>
                        @endif
                    </div>
                @empty
                    <div class="text-muted">No replenishment forecasts yet.</div>
                @endforelse
            </div>
            <div class="col-lg-6">
                <h3 class="h6">Cycle Counts</h3>
                @forelse($cycleCounts as $count)
                    <div class="d-flex justify-content-between gap-2 border-bottom py-2">
                        <div><strong>{{ $count->product?->name }}</strong><div class="small text-muted">{{ $count->bin?->bin_code }} · System {{ $count->system_quantity }} · Counted {{ $count->counted_quantity }}</div></div>
                        <span class="status-pill">{{ $count->variance_quantity }}</span>
                    </div>
                @empty
                    <div class="text-muted">No cycle counts yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@elseif($section === 'warehousing')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.warehousing.store') }}" class="row g-2">
            @csrf
            <div class="col-md-2"><input class="form-control" name="code" placeholder="Code" required></div>
            <div class="col-md-3"><input class="form-control" name="name" placeholder="Warehouse name" required></div>
            <div class="col-md-2"><input class="form-control" name="warehouse_type" value="Store Warehouse" required></div>
            <div class="col-md-2"><select class="form-select" name="branch_id"><option value="">Branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Active</option><option>Inactive</option></select></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.warehousing.zones.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" name="retail_warehouse_id" required><option value="">Warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="code" placeholder="Zone code" required></div>
                <div class="col-md-3"><input class="form-control" name="name" placeholder="Zone name" required></div>
                <div class="col-md-2"><input class="form-control" name="zone_type" placeholder="Zone type"></div>
                <div class="col-md-2"><button class="btn btn-outline-dark w-100">Add Zone</button></div>
            </form>
        </div>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.warehousing.bins.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" name="retail_warehouse_id" required><option value="">Warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="retail_warehouse_zone_id"><option value="">Zone</option>@foreach($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->warehouse?->name }} / {{ $zone->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><input class="form-control" name="aisle" placeholder="Aisle"></div>
                <div class="col-md-1"><input class="form-control" name="shelf" placeholder="Shelf"></div>
                <div class="col-md-2"><input class="form-control" name="bin_code" placeholder="Bin" required></div>
                <div class="col-md-1"><input class="form-control" name="capacity" type="number" step="0.001" placeholder="Cap"></div>
                <div class="col-md-1"><select class="form-select" name="status"><option>Active</option><option>Inactive</option><option>Blocked</option></select></div>
                <div class="col-md-1"><button class="btn btn-outline-dark w-100"><i class="bi bi-save"></i></button></div>
            </form>
        </div>
    </div>
@elseif($section === 'customers')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.customers.profile') }}" class="row g-2">
            @csrf
            <div class="col-md-4"><select class="form-select" name="client_id" required><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select class="form-select" name="customer_segment"><option>Retail Customer</option><option>VIP Customer</option><option>Wholesale Customer</option><option>Corporate Customer</option></select></div>
            <div class="col-md-4"><input class="form-control" name="customer_notes" placeholder="Shopping preferences and notes"></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.customers.offers.store') }}" class="row g-2">
                @csrf
                <div class="col-md-4"><select class="form-select" name="client_id" required><option value="">Offer customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input class="form-control" name="offer_name" placeholder="Personalized offer"></div>
                <div class="col-md-2"><select class="form-select" name="status"><option>Active</option><option>Draft</option><option>Paused</option><option>Expired</option></select></div>
                <div class="col-md-2"><input class="form-control" name="valid_until" type="date"></div>
                <div class="col-md-1"><button class="btn btn-outline-dark w-100"><i class="bi bi-bullseye"></i></button></div>
            </form>
        </div>
        @if($offers->isNotEmpty())
            <div class="border-top mt-3 pt-3">
                <h3 class="h6">Targeted Marketing Offers</h3>
                @foreach($offers as $offer)
                    <div class="d-flex justify-content-between gap-2 border-bottom py-2"><span>{{ $offer->client?->name }} · {{ $offer->offer_name }}</span><span class="status-pill">{{ $offer->status }}</span></div>
                @endforeach
            </div>
        @endif
    </div>
@elseif($section === 'loyalty')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.loyalty.enroll') }}" class="row g-2">
            @csrf
            <div class="col-md-10"><select class="form-select" name="client_id" required><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-success w-100">Enroll</button></div>
        </form>
    </div>
@elseif($section === 'promotions')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.promotions.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><input class="form-control" name="name" placeholder="Promotion name" required></div>
            <div class="col-md-2"><select class="form-select" name="promotion_type">@foreach($promotionTypes as $type)<option>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-2"><input class="form-control" name="discount_value" type="number" step="0.01" placeholder="Value" required></div>
            <div class="col-md-2"><input class="form-control" name="code" placeholder="Code"></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Draft</option><option>Active</option><option>Paused</option><option>Expired</option></select></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
    </div>
@elseif($section === 'gift-cards')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.gift-cards.issue') }}" class="row g-2">
            @csrf
            <div class="col-md-4"><select class="form-select" name="client_id"><option value="">Walk-in customer</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><input class="form-control" name="amount" type="number" step="0.01" placeholder="Amount" required></div>
            <div class="col-md-2"><input class="form-control" name="currency" value="KES" maxlength="3" required></div>
            <div class="col-md-2"><input class="form-control" name="expires_at" type="date"></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button></div>
        </form>
    </div>
@elseif($section === 'returns')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.returns.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><select class="form-select" name="pos_order_id" required><option value="">POS order</option>@foreach($orders as $order)<option value="{{ $order->id }}">{{ $order->order_number }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="return_type"><option>Return</option><option>Exchange</option><option>Refund</option><option>Defective Return</option></select></div>
            <div class="col-md-2"><select class="form-select" name="refund_method"><option>Original Payment</option><option>Store Credit</option><option>Gift Card</option><option>Cash</option></select></div>
            <div class="col-md-2"><input class="form-control" name="reason" placeholder="Reason" required></div>
            <input type="hidden" name="items[0][quantity]" value="1">
            <input type="hidden" name="items[0][condition]" value="Resellable">
            <div class="col-md-2"><input class="form-control" name="items[0][refund_amount]" type="number" step="0.01" placeholder="Refund" required></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
    </div>
@elseif($section === 'orders')
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Add Customer</h2>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('clients.index') }}">Open CRM</a>
        </div>
        <form method="POST" action="{{ route('retail.orders.customers.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><input class="form-control" name="name" placeholder="Customer name" required></div>
            <div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div>
            <div class="col-md-2"><input class="form-control" name="email" type="email" placeholder="Email"></div>
            <div class="col-md-2"><select class="form-select" name="customer_segment"><option>Retail Customer</option><option>VIP Customer</option><option>Wholesale Customer</option><option>Corporate Customer</option></select></div>
            <div class="col-md-2"><input class="form-control" name="address" placeholder="Address"></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-person-plus"></i></button></div>
        </form>
    </div>
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.orders.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><select class="form-select" name="client_id"><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(session('selectedCustomerId') == $client->id)>{{ $client->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="channel"><option>Store</option><option>Online Store</option><option>Mobile Commerce</option><option>Marketplace</option><option>Special Order</option></select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Draft</option><option>Pending</option><option>Confirmed</option><option>Packed</option><option>Shipped</option><option>Delivered</option><option>Cancelled</option></select></div>
            <div class="col-md-3"><select class="form-select" name="items[0][product_id]"><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <input type="hidden" name="items[0][title]" value="Manual retail order item">
            <div class="col-md-1"><input class="form-control" name="items[0][quantity]" type="number" step="0.001" value="1"></div>
            <div class="col-md-1"><input class="form-control" name="items[0][unit_price]" type="number" step="0.01" value="0"></div>
            <div class="col-md-12"><button class="btn btn-success">Save Order</button></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ $records->count() ? route('retail.orders.fulfillment.route', $records->first()) : '#' }}" class="row g-2">
                @csrf
                <div class="col-md-3"><select class="form-select" onchange="this.form.action=this.value" required><option value="">Route order</option>@foreach($records as $order)<option value="{{ route('retail.orders.fulfillment.route', $order) }}">{{ $order->order_number }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="fulfillment_type"><option>BOPIS</option><option>Ship From Store</option><option>Home Delivery</option><option>Marketplace Fulfillment</option></select></div>
                <div class="col-md-2"><select class="form-select" name="branch_id"><option value="">Auto branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="retail_warehouse_id"><option value="">Warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="carrier" placeholder="Carrier"></div>
                <div class="col-md-1"><button class="btn btn-outline-dark w-100"><i class="bi bi-signpost-split"></i></button></div>
            </form>
        </div>
        @if($fulfillments->isNotEmpty())
            <div class="border-top mt-3 pt-3">
                <h3 class="h6">Fulfillment Routing</h3>
                @foreach($fulfillments as $fulfillment)
                    <div class="d-flex justify-content-between gap-2 border-bottom py-2"><span>{{ $fulfillment->order?->order_number }} · {{ $fulfillment->fulfillment_type }} · {{ $fulfillment->branch?->name ?: 'Auto branch' }}</span><span class="status-pill">{{ $fulfillment->routing_status }}</span></div>
                @endforeach
            </div>
        @endif
    </div>
@elseif($section === 'procurement')
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Automated Reordering & Shared Procurement</h2>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('erp.procurement') }}">Open Shared Procurement</a>
        </div>
        <form method="POST" action="{{ route('retail.inventory.replenishment') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><select class="form-select" name="product_id" required><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select class="form-select" name="supplier_id"><option value="">Supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input class="form-control" name="forecast_period_days" type="number" value="30"></div>
            <div class="col-md-2"><input class="form-control" name="lead_time_days" type="number" value="7"></div>
            <div class="col-md-2"><button class="btn btn-success w-100">Generate Plan</button></div>
        </form>
        <div class="row g-3 border-top mt-3 pt-3">
            <div class="col-lg-6"><h3 class="h6">Supplier Contracts</h3>@forelse($contracts as $contract)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $contract->supplier?->name }} · {{ $contract->contract_number }}</span><span class="status-pill">{{ $contract->status }}</span></div>@empty<div class="text-muted">No supplier contracts yet.</div>@endforelse</div>
            <div class="col-lg-6"><h3 class="h6">Shared Purchase Orders</h3>@forelse($purchaseOrders as $po)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $po->po_number }} · {{ $po->supplier?->name }}</span><span>{{ number_format($po->amount, 2) }}</span></div>@empty<div class="text-muted">No purchase orders yet.</div>@endforelse</div>
        </div>
    </div>
@elseif($section === 'deliveries')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.deliveries.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><select class="form-select" name="retail_order_id" required><option value="">Order</option>@foreach($orders as $order)<option value="{{ $order->id }}">{{ $order->order_number }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="driver_id"><option value="">Driver</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Scheduled</option><option>In Transit</option><option>Delivered</option><option>Failed</option></select></div>
            <div class="col-md-2"><input class="form-control" name="scheduled_at" type="datetime-local"></div>
            <div class="col-md-2"><input class="form-control" name="delivery_address" placeholder="Address" required></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
    </div>
@elseif($section === 'suppliers')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.suppliers.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><input class="form-control" name="name" placeholder="Supplier name" required></div>
            <div class="col-md-2"><input class="form-control" name="supplier_code" placeholder="Code"></div>
            <div class="col-md-2"><input class="form-control" name="email" type="email" placeholder="Email"></div>
            <div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div>
            <div class="col-md-1"><input class="form-control" name="lead_time_days" type="number" placeholder="Lead"></div>
            <div class="col-md-1"><input class="form-control" name="rating" type="number" step="0.1" max="5" placeholder="Rate"></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ $records->count() ? route('retail.suppliers.contracts.store', $records->first()) : '#' }}" class="row g-2">
                @csrf
                <div class="col-md-2"><select class="form-select" onchange="this.form.action=this.value" required><option value="">Supplier contract</option>@foreach($records as $supplier)<option value="{{ route('retail.suppliers.contracts.store', $supplier) }}">{{ $supplier->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="contract_number" placeholder="Contract #" required></div>
                <div class="col-md-2"><select class="form-select" name="product_id"><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><input class="form-control" name="lead_time_days" type="number" placeholder="Lead"></div>
                <div class="col-md-2"><input class="form-control" name="payment_terms" placeholder="Payment terms"></div>
                <div class="col-md-2"><input class="form-control" name="service_level_agreement" placeholder="SLA"></div>
                <div class="col-md-1"><button class="btn btn-outline-dark w-100"><i class="bi bi-file-earmark-check"></i></button></div>
            </form>
        </div>
        @if($contracts->isNotEmpty())
            <div class="border-top mt-3 pt-3">
                <h3 class="h6">Contract Logs & Scorecards</h3>
                @foreach($contracts as $contract)
                    <div class="d-flex justify-content-between gap-2 border-bottom py-2"><span>{{ $contract->supplier?->name }} · {{ $contract->contract_number }} · Lead {{ $contract->lead_time_days }} days</span><span class="status-pill">{{ data_get($contract->scorecard, 'delivery_accuracy', 0) }}%</span></div>
                @endforeach
            </div>
        @endif
    </div>
@elseif($section === 'branches')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.branches.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><input class="form-control" name="name" placeholder="Branch name" required></div>
            <div class="col-md-2"><input class="form-control" name="code" placeholder="Code"></div>
            <div class="col-md-5"><input class="form-control" name="address" placeholder="Address"></div>
            <div class="col-md-1 form-check d-flex align-items-center ps-4"><input class="form-check-input me-2" type="checkbox" name="is_active" value="1" checked id="branchActive"><label class="form-check-label" for="branchActive">Active</label></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
    </div>
@elseif($section === 'ecommerce')
    <div class="card p-3 mb-3">
        <form method="POST" action="{{ route('retail.ecommerce.store') }}" class="row g-2">
            @csrf
            <div class="col-md-2"><input class="form-control" name="channel" placeholder="Website, mobile, marketplace" required></div>
            <div class="col-md-2"><input class="form-control" name="external_store_id" placeholder="External store ID"></div>
            <div class="col-md-3"><input class="form-control" name="website_url" type="url" placeholder="https://store.example.com"></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Draft</option><option>Active</option><option>Paused</option><option>Disconnected</option></select></div>
            <div class="col-md-2 d-flex flex-wrap gap-3 align-items-center">
                @foreach(['product_sync' => 'Products', 'inventory_sync' => 'Inventory', 'order_sync' => 'Orders', 'customer_sync' => 'Customers'] as $name => $label)
                    <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="{{ $name }}" value="1" checked>{{ $label }}</label>
                @endforeach
            </div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <h2 class="h5 mb-2">Website Catalog Feed</h2>
            @forelse($records as $integration)
                @php($apiKey = data_get($integration->settings, 'api_key'))
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                            <strong>{{ $integration->channel }}</strong>
                            <div class="small text-muted">{{ data_get($integration->settings, 'website_url') ?: 'No website URL set' }}</div>
                        </div>
                        <span class="status-pill">{{ $integration->status }}</span>
                    </div>
                    @if($apiKey)
                        <div class="row g-2 mt-2">
                            <div class="col-lg-4"><label class="small text-muted fw-bold">Products</label><input class="form-control form-control-sm" readonly value="{{ route('api.v1.public.retail.ecommerce.products', $integration) }}?api_key={{ $apiKey }}"></div>
                            <div class="col-lg-4"><label class="small text-muted fw-bold">Categories</label><input class="form-control form-control-sm" readonly value="{{ route('api.v1.public.retail.ecommerce.categories', $integration) }}?api_key={{ $apiKey }}"></div>
                            <div class="col-lg-4"><label class="small text-muted fw-bold">Pricing</label><input class="form-control form-control-sm" readonly value="{{ route('api.v1.public.retail.ecommerce.pricing', $integration) }}?api_key={{ $apiKey }}"></div>
                        </div>
                        <div class="small text-muted mt-2">Websites can also send the key as <code>Authorization: Bearer {{ $apiKey }}</code> or <code>X-Retail-Api-Key</code>.</div>
                    @else
                        <form method="POST" action="{{ route('retail.ecommerce.sync', $integration) }}" class="mt-2">
                            @csrf
                            <button class="btn btn-sm btn-outline-dark">Generate Feed Key</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="text-muted">Create a website integration to generate product, category, and pricing feed URLs.</div>
            @endforelse
        </div>
    </div>
@endif

<div class="card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Record</th><th>Status</th><th>Details</th><th>Updated</th><th></th></tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    <td class="fw-semibold">
                        {{ $record->name ?? $record->order_number ?? $record->card_number ?? $record->return_number ?? $record->client?->name ?? $record->product?->name ?? $record->title ?? '#'.$record->id }}
                    </td>
                    <td><span class="status-pill">{{ $record->status ?? $record->approval_status ?? 'Active' }}</span></td>
                    <td class="text-muted">
                        {{ $record->code ?? $record->sku ?? $record->promotion_type ?? $record->channel ?? $record->customer_segment ?? $record->warehouse_type ?? $record->reason ?? $record->email ?? '' }}
                    </td>
                    <td>{{ optional($record->updated_at)->format('d M Y') }}</td>
                    <td class="text-end">
                        @if($section === 'returns' && ($record->approval_status ?? null) !== 'Approved')
                            <form method="POST" action="{{ route('retail.returns.approve', $record) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-success">Approve</button>
                            </form>
                        @elseif($section === 'ecommerce')
                            <form method="POST" action="{{ route('retail.ecommerce.sync', $record) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-dark">Sync</button>
                            </form>
                        @elseif($section === 'gift-cards')
                            <form method="POST" action="{{ route('retail.gift-cards.recharge', $record) }}" class="d-flex gap-1 justify-content-end">
                                @csrf
                                <input class="form-control form-control-sm" name="amount" type="number" step="0.01" min="0.01" placeholder="Amount" style="max-width:110px">
                                <button class="btn btn-sm btn-outline-dark">Recharge</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted p-4">No records yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($records, 'links'))
        <div class="p-3">{{ $records->links() }}</div>
    @endif
</div>
@endsection
