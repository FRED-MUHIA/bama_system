@extends('layouts.app')
@section('title', $title)

@section('content')
@include('retail.partials.nav')

<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $title }}</h1>
        <div class="text-muted">Simple records for the active shop.</div>
    </div>
    @if($section === 'customers')
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <a class="btn btn-success" href="#retail-add-customer"><i class="bi bi-person-plus me-1"></i>Add Customer</a>
            <a class="btn btn-outline-dark" href="{{ route('clients.index') }}"><i class="bi bi-people me-1"></i>Open CRM</a>
        </div>
    @endif
    </div>

@if($section === 'customers')
    <div class="card p-3 mb-3" id="retail-add-customer">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Add Customer</h2>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('clients.index') }}">Open CRM</a>
        </div>
        <form method="POST" action="{{ route('retail.customers.store') }}" class="row g-2">
            @csrf
            <div class="col-md-2">
                <select class="form-select" name="type">
                    <option value="individual">Individual</option>
                    <option value="company">Company</option>
                </select>
            </div>
            <div class="col-md-3"><input class="form-control" name="name" value="{{ old('name') }}" placeholder="Customer name" required></div>
            <div class="col-md-2"><input class="form-control" name="phone" value="{{ old('phone') }}" placeholder="Phone"></div>
            <div class="col-md-2"><input class="form-control" name="email" type="email" value="{{ old('email') }}" placeholder="Email"></div>
            <div class="col-md-3"><input class="form-control" name="company_name" value="{{ old('company_name') }}" placeholder="Company"></div>
            <div class="col-md-3"><select class="form-select" name="customer_segment"><option>Retail Customer</option><option>VIP Customer</option><option>Wholesale Customer</option><option>Corporate Customer</option></select></div>
            <div class="col-md-4"><input class="form-control" name="address" value="{{ old('address') }}" placeholder="Address"></div>
            <div class="col-md-4"><input class="form-control" name="notes" value="{{ old('notes') }}" placeholder="Notes"></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-person-plus me-1"></i>Add</button></div>
        </form>
    </div>

    <div class="card p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Customer</th><th>Status</th><th>Orders</th><th>Details</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    @php
                        $posOrdersCount = (int) ($record->pos_orders_count ?? 0);
                        $retailOrdersCount = (int) ($record->retail_orders_count ?? 0);
                        $orderCount = $posOrdersCount + $retailOrdersCount;
                        $totalSpend = (float) ($record->pos_order_total ?? 0) + (float) ($record->retail_order_total ?? 0);
                    @endphp
                    <tr>
                        <td class="fw-semibold">
                            <a class="text-decoration-none fw-bold text-black" href="{{ route('retail.customers.show', $record) }}">{{ $record->name }}</a>
                            <div class="small text-muted">{{ $record->company_name ?: ($record->email ?: 'No email recorded') }}</div>
                        </td>
                        <td><span class="status-pill">{{ $record->retailProfile?->customer_segment ?? 'Active' }}</span></td>
                        <td>
                            <strong>{{ number_format($orderCount) }}</strong>
                            <div class="small text-muted">{{ $posOrdersCount }} POS / {{ $retailOrdersCount }} retail</div>
                        </td>
                        <td class="text-muted">
                            {{ $record->phone ?: 'No phone' }}
                            <div>Total spend {{ number_format($totalSpend, 2) }}</div>
                        </td>
                        <td>{{ optional($record->updated_at)->format('d M Y') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('retail.customers.show', $record) }}"><i class="bi bi-eye me-1"></i>View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted p-4">No customers yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($records, 'links'))
            <div class="p-3">{{ $records->links() }}</div>
        @endif
    </div>
@elseif($section === 'products')
    <div class="mb-3" id="retail-add-product">
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
    <div class="card p-3 mb-3">
        <h2 class="h5">Add Category</h2>
        <form method="post" action="{{ route('product-categories.store') }}" class="row g-2">@csrf
            <div class="col-12"><select class="form-select" name="parent_id"><option value="">Top level</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ method_exists($category, 'path') ? $category->path() : $category->name }}</option>@endforeach</select></div>
            <div class="col-12"><input class="form-control" name="name" placeholder="Category name" required></div>
            <div class="col-6"><input class="form-control" name="code" placeholder="Code"></div>
            <div class="col-6"><input class="form-control" type="number" min="0" name="sort_order" placeholder="Sort"></div>
            <div class="col-12"><input class="form-control" name="icon" placeholder="Icon"></div>
            <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
            <div class="col-12"><button class="btn btn-success btn-sm">Save Category</button></div>
        </form>
        <hr>
        @foreach($categories as $category)
            <div class="border-top py-2">
                {{ method_exists($category, 'path') ? $category->path() : $category->name }}
                <div class="small text-muted">{{ $category->description }}</div>
                @if(($attributes ?? collect())->isNotEmpty())
                    <form method="post" action="{{ route('product-categories.attributes.store', $category) }}" class="row g-1 mt-2">@csrf
                        <div class="col-12"><select class="form-select form-select-sm" name="product_attribute_id">@foreach($attributes as $attribute)<option value="{{ $attribute->id }}">{{ $attribute->name }}</option>@endforeach</select></div>
                        <div class="col-4"><label class="form-check small"><input class="form-check-input" type="checkbox" name="is_required" value="1"> Required</label></div>
                        <div class="col-4"><label class="form-check small"><input class="form-check-input" type="checkbox" name="is_variant_attribute" value="1"> Variant</label></div>
                        <div class="col-4"><button class="btn btn-sm btn-outline-dark w-100">Attach</button></div>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
    <div class="card p-3 mb-3">
        <h2 class="h5">Brands</h2>
        <form method="post" action="{{ route('product-brands.store') }}" class="row g-2">@csrf
            <div class="col-12"><input class="form-control" name="name" placeholder="Brand / manufacturer" required></div>
            <div class="col-6"><input class="form-control" name="code" placeholder="Code"></div>
            <div class="col-6"><select class="form-select" name="status"><option>Active</option><option>Inactive</option></select></div>
            <div class="col-12"><input class="form-control" name="website_url" placeholder="Website"></div>
            <div class="col-12"><button class="btn btn-success btn-sm">Save Brand</button></div>
        </form>
        <hr>
        @forelse(($brands ?? []) as $brand)<div class="border-top py-2">{{ $brand->name }}<div class="small text-muted">{{ $brand->code ?: 'No code' }} · {{ $brand->status }}</div></div>@empty<div class="text-muted">No brands yet.</div>@endforelse
    </div>
    <div class="card p-3 mb-3">
        <h2 class="h5">Attributes</h2>
        <form method="post" action="{{ route('product-attributes.store') }}" class="row g-2">@csrf
            <div class="col-12"><input class="form-control" name="name" placeholder="Attribute name" required></div>
            <div class="col-6"><input class="form-control" name="code" placeholder="Code"></div>
            <div class="col-6"><select class="form-select" name="display_type">@foreach(\App\Models\ProductAttribute::DISPLAY_TYPES as $type)<option>{{ $type }}</option>@endforeach</select></div>
            <div class="col-6"><input class="form-control" name="unit" placeholder="Unit"></div>
            <div class="col-6"><input class="form-control" type="number" min="0" name="sort_order" placeholder="Sort"></div>
            <div class="col-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_variant_attribute" value="1"> <span class="form-check-label">Variant</span></label></div>
            <div class="col-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_filterable" value="1"> <span class="form-check-label">Filter</span></label></div>
            <div class="col-12"><button class="btn btn-success btn-sm">Save Attribute</button></div>
        </form>
        <hr>
        @forelse(($attributes ?? []) as $attribute)
            <div class="border-top py-2">
                <strong>{{ $attribute->name }}</strong>
                <div class="small text-muted">{{ $attribute->display_type }} · {{ $attribute->values->pluck('value')->take(5)->join(', ') }}</div>
                <form method="post" action="{{ route('product-attributes.values.store', $attribute) }}" class="row g-2 mt-1">@csrf
                    <div class="col-7"><input class="form-control form-control-sm" name="value" placeholder="Value" required></div>
                    <div class="col-3"><input class="form-control form-control-sm" name="code" placeholder="Code"></div>
                    <div class="col-2"><button class="btn btn-sm btn-success w-100">+</button></div>
                </form>
            </div>
        @empty
            <div class="text-muted">No attributes yet.</div>
        @endforelse
    </div>
@elseif($section === 'inventory')
    <div class="card p-3 mb-3" id="retail-stock-records">
        <h2 class="h5">Stock Records</h2>
        <div class="text-muted small mb-3">Every sale automatically deducts stock and is recorded here — showing what was sold and what remains.</div>
        @forelse($stockMovements as $movement)
            <div class="d-flex justify-content-between gap-2 border-bottom py-2">
                <div class="min-w-0">
                    <strong>{{ $movement->product?->name ?: 'Deleted product' }}</strong>
                    @if($movement->product?->sku)<span class="small text-muted"> · {{ $movement->product->sku }}</span>@endif
                    <div class="small text-muted">{{ $movement->created_at?->format('d M Y H:i') }} · {{ $movement->reference }}</div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="{{ $movement->quantity < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 3) }}</div>
                    <div class="small text-muted">Remaining {{ number_format($movement->balance_after, 3) }}</div>
                </div>
            </div>
        @empty
            <div class="text-muted py-2">No stock records yet. Sales will be recorded here automatically.</div>
        @endforelse
    </div>
    <div class="card p-3 mb-3">
        <h2 class="h5">Update Stock</h2>
        <div class="text-muted small mb-3">Add new stock, remove damaged stock, or correct a balance. Pick the product first.</div>
        <form method="POST" class="row g-2">
            @csrf
            <div class="col-md-4"><select class="form-select" name="product_id" required onchange="this.form.action=this.value"><option value="">Product</option>@foreach($products as $product)<option value="{{ route('products.stock.update', $product) }}">{{ $product->name }} ({{ $product->formattedStock() }} in stock)</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="type"><option>Add</option><option>Remove</option><option>Set</option></select></div>
            <div class="col-md-2"><input class="form-control" name="quantity" type="number" min="0" step="0.001" placeholder="Qty" required></div>
            <div class="col-md-3"><input class="form-control" name="notes" placeholder="Notes (optional)"></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
    </div>
    <div class="card p-3 mb-3">
        <details>
            <summary class="h5 mb-0 list-none">Advanced tools — reserve, transfer, forecasts, cycle counts</summary>
            <div class="mt-3">
        <form method="POST" action="{{ route('retail.inventory.adjust') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><select class="form-select" name="product_id" required><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="movement"><option>Add</option><option>Remove</option><option>Set</option></select></div>
            <div class="col-md-2"><input class="form-control" name="quantity" type="number" min="0" step="0.001" placeholder="Qty" required></div>
            <div class="col-md-2"><select class="form-select" name="bucket"><option value="available_stock">Available</option><option value="reserved_stock">Reserved</option><option value="in_transit_stock">In Transit</option><option value="damaged_stock">Damaged</option></select></div>
            <div class="col-md-2"><select class="form-select" name="branch_id"><option value="">Any branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button></div>
            <div class="col-md-3"><select class="form-select" name="retail_warehouse_id"><option value="">Any warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select class="form-select" name="retail_warehouse_bin_id"><option value="">Any bin</option>@foreach($bins as $bin)<option value="{{ $bin->id }}">{{ $bin->warehouse?->name }} / {{ $bin->bin_code }}</option>@endforeach</select></div>
            <div class="col-md-3"><input class="form-control" name="reference" placeholder="Reference"></div>
            <div class="col-md-3"><input class="form-control" name="notes" placeholder="Notes"></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.inventory.reserve') }}" class="row g-2">
                @csrf
                <div class="col-md-4"><select class="form-select" name="product_id" required><option value="">Reserve product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="quantity" type="number" step="0.001" placeholder="Qty" required></div>
                <div class="col-md-2"><select class="form-select" name="branch_id"><option value="">Any branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input class="form-control" name="reference" placeholder="Reservation reference"></div>
                <div class="col-md-1"><button class="btn btn-outline-dark w-100"><i class="bi bi-lock"></i></button></div>
            </form>
        </div>
        <div class="border-top mt-3 pt-3">
            <form method="POST" action="{{ route('retail.inventory.transfer') }}" class="row g-2">
                @csrf
                <div class="col-md-4"><select class="form-select" name="product_id" required><option value="">Transfer product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="quantity" type="number" step="0.001" placeholder="Qty" required></div>
                <div class="col-md-2"><select class="form-select" name="from_branch_id"><option value="">From branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="to_branch_id"><option value="">To branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
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
        </details>
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
    <div class="card p-3 mb-3" id="retail-add-customer">
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
    <div class="card p-3 mb-3" id="retail-place-order">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Create Retail Order</h2>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('retail.pos.index') }}"><i class="bi bi-upc-scan me-1"></i>Open POS</a>
        </div>
        <form method="POST" action="{{ route('retail.orders.store') }}" class="row g-2" data-retail-order-form>
            @csrf
            <div class="col-md-3"><select class="form-select" name="client_id"><option value="">Customer</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(session('selectedCustomerId') == $client->id)>{{ $client->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="channel"><option>Store</option><option>Online Store</option><option>Mobile Commerce</option><option>Marketplace</option><option>Special Order</option></select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Draft</option><option>Pending</option><option>Confirmed</option><option>Packed</option><option>Shipped</option><option>Delivered</option><option>Cancelled</option></select></div>
            <div class="col-md-5 text-md-end"><button class="btn btn-sm btn-outline-dark" type="button" data-add-retail-order-item><i class="bi bi-plus-lg me-1"></i>Add Product</button></div>
            <div class="col-12 d-grid gap-2" data-retail-order-items>
                <div class="row g-2 align-items-center" data-retail-order-item>
                    <div class="col-md-3"><select class="form-select" name="items[0][product_id]" data-order-product><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->price }}" data-name="{{ $product->name }}" data-description="{{ $product->description ?: $product->name }}">{{ $product->name }} - {{ number_format((float) $product->price, 2) }}</option>@endforeach</select></div>
                    <div class="col-md-3"><input class="form-control" name="items[0][title]" data-order-title placeholder="Item title" required></div>
                    <input type="hidden" name="items[0][description]" data-order-description>
                    <input type="hidden" name="items[0][discount]" value="0">
                    <div class="col-md-2"><input class="form-control" name="items[0][quantity]" data-order-quantity type="number" min="0.001" step="0.001" value="1" placeholder="Qty" required></div>
                    <div class="col-md-2"><input class="form-control" name="items[0][unit_price]" data-order-price type="number" min="0" step="0.01" placeholder="Unit price" required></div>
                    <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-retail-item><i class="bi bi-trash"></i></button></div>
                </div>
            </div>
            <div class="col-md-12"><button class="btn btn-success">Save Order</button></div>
        </form>
        <div class="border-top mt-3 pt-3" id="retail-pos-order">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 mb-0">Create POS Order</h3>
                <a class="btn btn-sm btn-outline-dark" href="{{ route('pos-orders.create') }}"><i class="bi bi-cart-plus me-1"></i>Full POS Form</a>
            </div>
            <form method="POST" action="{{ route('retail.orders.pos.store') }}" class="row g-2" data-retail-pos-order-form>
                @csrf
                <input type="hidden" name="sale_type" value="Sale">
                <input type="hidden" name="channel" value="Store">
                <input type="hidden" name="customer_type" value="Retail Customer">
                <div class="col-md-3"><select class="form-select" name="client_id"><option value="">Walk-in customer</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(session('selectedCustomerId') == $client->id)>{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="customer_name" placeholder="Customer name"></div>
                <div class="col-md-2"><input class="form-control" name="customer_phone" placeholder="Phone"></div>
                <div class="col-md-2"><select class="form-select" name="branch_id"><option value="">Store / Branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-md-3 text-md-end"><button class="btn btn-sm btn-outline-dark" type="button" data-add-retail-pos-item><i class="bi bi-plus-lg me-1"></i>Add Product</button></div>
                <div class="col-12 d-grid gap-2" data-retail-pos-items>
                    <div class="row g-2 align-items-center" data-retail-pos-item>
                        <div class="col-md-3"><select class="form-select" name="items[0][product_id]" data-retail-pos-product><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->price }}" data-name="{{ $product->name }}" data-description="{{ $product->description ?: $product->name }}">{{ $product->name }} - {{ number_format((float) $product->price, 2) }}</option>@endforeach</select></div>
                        <input type="hidden" name="items[0][title]" data-retail-pos-title>
                        <input type="hidden" name="items[0][description]" data-retail-pos-description>
                        <input type="hidden" name="items[0][discount]" value="0">
                        <div class="col-md-2"><input class="form-control" name="items[0][quantity]" data-retail-pos-quantity type="number" min="0.001" step="0.001" value="1" placeholder="Qty"></div>
                        <div class="col-md-2"><input class="form-control" name="items[0][unit_price]" data-retail-pos-price type="number" min="0" step="0.01" placeholder="Unit price"></div>
                        <div class="col-md-2"><div class="form-control bg-light" data-retail-pos-line-total>0.00</div></div>
                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-retail-item><i class="bi bi-trash"></i></button></div>
                    </div>
                </div>
                <div class="col-md-2"><select class="form-select" name="payments[0][payment_method_id]"><option value="">Payment method</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="payments[0][method_type]" value="Cash" placeholder="Payment type"></div>
                <div class="col-md-2"><input class="form-control" name="payments[0][amount]" data-retail-pos-payment type="number" min="0" step="0.01" placeholder="Amount paid"></div>
                <div class="col-md-2"><input class="form-control" name="payments[0][reference]" placeholder="Reference"></div>
                <div class="col-md-2"><input class="form-control" name="notes" placeholder="POS note"></div>
                <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-cart-check"></i></button></div>
            </form>
        </div>
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
        <form method="POST" action="{{ route('retail.ecommerce.store') }}" class="row g-2 align-items-end">
            @csrf
            <input type="hidden" name="channel" value="Website">
            <input type="hidden" name="status" value="Active">
            @foreach(['product_sync', 'inventory_sync', 'order_sync', 'customer_sync'] as $name)
                <input type="hidden" name="{{ $name }}" value="1">
            @endforeach
            <div class="col-md-7">
                <label class="small text-muted fw-bold">Website URL</label>
                <input class="form-control" name="website_url" type="url" placeholder="https://yourshop.com">
            </div>
            <div class="col-md-3">
                <label class="small text-muted fw-bold">Store label</label>
                <input class="form-control" name="external_store_id" placeholder="Main shop">
            </div>
            <div class="col-md-2"><button class="btn btn-success w-100"><i class="bi bi-save me-1"></i>Save</button></div>
        </form>
        <div class="border-top mt-3 pt-3">
            <h2 class="h5 mb-2">Catalog Feed</h2>
            @forelse($records as $integration)
                @php
                    $apiKey = data_get($integration->settings, 'api_key');
                @endphp
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
                    @else
                        <form method="POST" action="{{ route('retail.ecommerce.sync', $integration) }}" class="mt-2">
                            @csrf
                            <button class="btn btn-sm btn-outline-dark">Generate Feed Key</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="text-muted">No website catalog has been saved yet.</div>
            @endforelse
        </div>
    </div>
@endif

@if($section === 'products')
    <div class="card p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Catalog</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="fw-semibold">
                            {{ $record->name }}
                            <div class="small text-muted">{{ $record->sku ?: 'No SKU' }} @if($record->barcode) · {{ $record->barcode }} @endif</div>
                        </td>
                        <td>
                            {{ number_format((float) $record->price, 2) }}
                            <div class="small text-muted">Cost {{ number_format((float) $record->cost_price, 2) }}</div>
                        </td>
                        <td>
                            <strong>{{ $record->formattedStock() }}</strong>
                            @if($record->isLowStock())<div class="small text-danger fw-bold">Low stock</div>@endif
                        </td>
                        <td class="text-muted">{{ $record->category?->name ?: 'No category' }} @if($record->brand) · {{ $record->brand->name }} @endif</td>
                        <td>{{ optional($record->updated_at)->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <button class="btn btn-sm btn-outline-success" type="button" data-retail-panel-toggle="retail-product-stock-row-{{ $record->id }}" aria-expanded="false" aria-controls="retail-product-stock-row-{{ $record->id }}"><i class="bi bi-boxes"></i></button>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-retail-panel-toggle="retail-product-edit-row-{{ $record->id }}" aria-expanded="false" aria-controls="retail-product-edit-row-{{ $record->id }}"><i class="bi bi-pencil"></i></button>
                                <form method="post" action="{{ route('products.destroy', $record) }}" onsubmit="return confirm('Archive this product?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Archive {{ $record->name }}"><i class="bi bi-archive"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    <tr id="retail-product-stock-row-{{ $record->id }}" class="d-none" hidden>
                        <td colspan="6" class="p-0 border-0">
                            <div class="border-top p-3">
                                <form method="post" action="{{ route('products.stock.update', $record) }}" class="row g-2 align-items-end">@csrf
                                    <div class="col-md-3"><label class="form-label">Movement</label><select class="form-select" name="type"><option>Add</option><option>Remove</option><option>Set</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Quantity ({{ $record->stock_unit ?: 'pcs' }})</label><input class="form-control" name="quantity" type="number" min="0" step="0.001" required></div>
                                    <div class="col-md-4"><label class="form-label">Notes</label><input class="form-control" name="notes" placeholder="Reason"></div>
                                    <div class="col-md-2"><button class="btn btn-success btn-sm w-100">Update Stock</button></div>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="retail-product-edit-row-{{ $record->id }}" class="d-none" hidden>
                        <td colspan="6" class="p-0 border-0">
                            <div class="border-top p-3">
                                <form method="post" action="{{ route('products.update', $record) }}" class="row g-2">@csrf @method('PUT')
                                    @include('products.partials.fields', ['product' => $record])
                                    @include('products.partials.variant-generator', ['product' => $record])
                                    <div class="col-12"><button class="btn btn-warning btn-sm">Update Product</button></div>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted p-4">No products yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($records, 'links'))
            <div class="p-3">{{ $records->links() }}</div>
        @endif
    </div>
@elseif($section === 'inventory')
    <div class="card p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Stock Item</th><th>Location</th><th>Available</th><th>Reserved</th><th>Other</th><th></th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    @php
                        $stockProduct = $record->variant?->product ?: $record->product;
                        $location = collect([$record->branch?->name, $record->warehouse?->name, $record->bin?->bin_code])->filter()->join(' / ');
                    @endphp
                    <tr>
                        <td class="fw-semibold">
                            {{ $record->product?->name ?? 'Unknown product' }}
                            @if($record->variant)
                                <div class="small text-muted">{{ $record->variant->displayName() }} · {{ $record->variant->sku }}</div>
                            @elseif($record->product?->sku)
                                <div class="small text-muted">{{ $record->product->sku }}</div>
                            @endif
                        </td>
                        <td class="text-muted">{{ $location ?: 'Default stock' }}</td>
                        <td><strong>{{ $stockProduct?->formattedStock((float) $record->available_stock) ?? number_format((float) $record->available_stock, 3) }}</strong></td>
                        <td>{{ $stockProduct?->formattedStock((float) $record->reserved_stock) ?? number_format((float) $record->reserved_stock, 3) }}</td>
                        <td class="text-muted">
                            Transit {{ $stockProduct?->formattedStock((float) $record->in_transit_stock) ?? number_format((float) $record->in_transit_stock, 3) }}
                            <div>Damaged {{ $stockProduct?->formattedStock((float) $record->damaged_stock) ?? number_format((float) $record->damaged_stock, 3) }}</div>
                        </td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-success" type="button" data-retail-panel-toggle="retail-inventory-edit-row-{{ $record->id }}" aria-expanded="false" aria-controls="retail-inventory-edit-row-{{ $record->id }}"><i class="bi bi-boxes me-1"></i>Stock</button></td>
                    </tr>
                    <tr id="retail-inventory-edit-row-{{ $record->id }}" class="d-none" hidden>
                        <td colspan="6" class="p-0 border-0">
                            <div class="border-top p-3">
                                <form method="POST" action="{{ route('retail.inventory.adjust') }}" class="row g-2 align-items-end">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $record->product_id }}">
                                    <input type="hidden" name="retail_product_variant_id" value="{{ $record->retail_product_variant_id }}">
                                    <input type="hidden" name="branch_id" value="{{ $record->branch_id }}">
                                    <input type="hidden" name="retail_warehouse_id" value="{{ $record->retail_warehouse_id }}">
                                    <input type="hidden" name="retail_warehouse_bin_id" value="{{ $record->retail_warehouse_bin_id }}">
                                    <div class="col-md-2"><label class="form-label">Movement</label><select class="form-select" name="movement"><option>Set</option><option>Add</option><option>Remove</option></select></div>
                                    <div class="col-md-2"><label class="form-label">Bucket</label><select class="form-select" name="bucket"><option value="available_stock">Available</option><option value="reserved_stock">Reserved</option><option value="in_transit_stock">In Transit</option><option value="damaged_stock">Damaged</option></select></div>
                                    <div class="col-md-2"><label class="form-label">Quantity</label><input class="form-control" name="quantity" type="number" min="0" step="0.001" value="{{ (float) $record->available_stock }}" required></div>
                                    <div class="col-md-3"><label class="form-label">Reference</label><input class="form-control" name="reference" value="Balance #{{ $record->id }}"></div>
                                    <div class="col-md-3"><button class="btn btn-success btn-sm w-100">Update Stock</button></div>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted p-4">No stock balances yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($records, 'links'))
            <div class="p-3">{{ $records->links() }}</div>
        @endif
    </div>
@elseif($section !== 'customers')
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
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-retail-panel-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const panel = document.getElementById(button.dataset.retailPanelToggle);
            if (! panel) return;

            const shouldOpen = panel.classList.contains('d-none');
            panel.classList.toggle('d-none', ! shouldOpen);
            panel.toggleAttribute('hidden', ! shouldOpen);
            button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        });
    });

    const retailOrderItems = document.querySelector('[data-retail-order-items]');
    const retailPosItems = document.querySelector('[data-retail-pos-items]');
    const retailPosPayment = document.querySelector('[data-retail-pos-payment]');

    const renumberItemRows = (container, rowSelector) => {
        if (! container) return;

        const rows = container.querySelectorAll(rowSelector);
        rows.forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
            });
        });
    };

    const resetItemRow = (row) => {
        row.querySelectorAll('select').forEach((select) => select.selectedIndex = 0);
        row.querySelectorAll('input').forEach((input) => {
            if (input.matches('[data-order-quantity], [data-retail-pos-quantity]')) {
                input.value = '1';
            } else if (input.name.endsWith('[discount]')) {
                input.value = '0';
            } else {
                input.value = '';
            }
        });
        row.querySelectorAll('[data-retail-pos-line-total]').forEach((lineTotal) => lineTotal.textContent = '0.00');
    };

    const cloneItemRow = (container, rowSelector) => {
        if (! container) return;

        const source = container.querySelector(rowSelector);
        if (! source) return;

        const row = source.cloneNode(true);
        resetItemRow(row);
        container.appendChild(row);
        renumberItemRows(container, rowSelector);
    };

    const selectedProduct = (select) => {
        const option = select?.selectedOptions?.[0];

        return {
            name: option?.dataset.name || '',
            description: option?.dataset.description || option?.dataset.name || '',
            price: option?.dataset.price || '',
        };
    };

    const fillRetailOrderRow = (row) => {
        const product = selectedProduct(row.querySelector('[data-order-product]'));
        const title = row.querySelector('[data-order-title]');
        const description = row.querySelector('[data-order-description]');
        const price = row.querySelector('[data-order-price]');

        if (title && product.name) title.value = product.name;
        if (description) description.value = product.description;
        if (price && product.price) price.value = product.price;
    };

    const fillRetailPosRow = (row) => {
        const product = selectedProduct(row.querySelector('[data-retail-pos-product]'));
        const title = row.querySelector('[data-retail-pos-title]');
        const description = row.querySelector('[data-retail-pos-description]');
        const price = row.querySelector('[data-retail-pos-price]');

        if (title) title.value = product.name;
        if (description) description.value = product.description;
        if (price && product.price) price.value = product.price;
    };

    const updateRetailPosTotals = () => {
        if (! retailPosItems || ! retailPosPayment) return;

        let total = 0;
        retailPosItems.querySelectorAll('[data-retail-pos-item]').forEach((row) => {
            const quantity = Number.parseFloat(row.querySelector('[data-retail-pos-quantity]')?.value || '0');
            const price = Number.parseFloat(row.querySelector('[data-retail-pos-price]')?.value || '0');
            const lineTotal = Math.max(quantity * price, 0);
            total += lineTotal;

            const display = row.querySelector('[data-retail-pos-line-total]');
            if (display) display.textContent = lineTotal.toFixed(2);
        });

        retailPosPayment.value = total ? total.toFixed(2) : '';
    };

    document.querySelector('[data-add-retail-order-item]')?.addEventListener('click', () => {
        cloneItemRow(retailOrderItems, '[data-retail-order-item]');
    });

    document.querySelector('[data-add-retail-pos-item]')?.addEventListener('click', () => {
        cloneItemRow(retailPosItems, '[data-retail-pos-item]');
        updateRetailPosTotals();
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-retail-item]');
        if (! button) return;

        const orderRow = button.closest('[data-retail-order-item]');
        const posRow = button.closest('[data-retail-pos-item]');
        const container = orderRow ? retailOrderItems : retailPosItems;
        const selector = orderRow ? '[data-retail-order-item]' : '[data-retail-pos-item]';
        const rows = container?.querySelectorAll(selector);

        if (! rows || rows.length <= 1) return;

        (orderRow || posRow).remove();
        renumberItemRows(container, selector);
        updateRetailPosTotals();
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-order-product]')) {
            fillRetailOrderRow(event.target.closest('[data-retail-order-item]'));
        }

        if (event.target.matches('[data-retail-pos-product]')) {
            fillRetailPosRow(event.target.closest('[data-retail-pos-item]'));
            updateRetailPosTotals();
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-retail-pos-quantity], [data-retail-pos-price]')) {
            updateRetailPosTotals();
        }
    });
});
</script>
@endpush
