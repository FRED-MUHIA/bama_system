@extends('layouts.app')
@section('title','Products')
@section('content')
@if($lowStockProducts->isNotEmpty())
    <div class="alert alert-warning">
        <strong>Low stock:</strong>
        {{ $lowStockProducts->take(6)->map(fn($item) => $item->name.' ('.$item->formattedStock().' remaining)')->join(', ') }}
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Fix these product details:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="row g-4">
    <div class="col-lg-8 order-2 order-lg-1">
        <div class="card"><div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0">Product Catalog</h2>
                <a class="btn btn-sm btn-warning d-lg-none" href="#add-product">Add Product</a>
            </div>
            <div class="d-grid gap-3 d-lg-none">
                @forelse($products as $item)
                    <div class="border rounded-3 p-3">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="min-w-0">
                                <h3 class="h6 mb-1">{{ $item->name }}</h3>
                                <div class="small text-muted">{{ $item->category?->name ?: 'No category' }} @if($item->sku) · {{ $item->sku }} @endif</div>
                            </div>
                            <span class="status-pill flex-shrink-0">{{ $item->is_active ? 'active' : 'inactive' }}</span>
                        </div>
                        @if($item->description)
                            <div class="small text-muted mt-2">{{ $item->description }}</div>
                        @endif
                        <div class="row g-2 mt-2 small">
                            <div class="col-6"><span class="text-muted d-block">Price</span><strong>{{ number_format($item->price,2) }}</strong></div>
                            <div class="col-6"><span class="text-muted d-block">Cost</span><strong>{{ number_format($item->cost_price,2) }}</strong></div>
                            <div class="col-6">
                                <span class="text-muted d-block">Stock</span>
                                <strong>{{ $item->formattedStock() }}</strong>
                                @if($item->isLowStock())<div class="text-danger fw-bold">Low stock</div>@endif
                            </div>
                            <div class="col-6"><span class="text-muted d-block">Reorder</span><strong>{{ $item->formattedStock((float) $item->reorder_level) }}</strong></div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-sm btn-outline-success flex-fill" type="button" data-bs-toggle="collapse" data-bs-target="#mobile-stock-product-{{ $item->id }}">Stock</button>
                            <button class="btn btn-sm btn-outline-dark flex-fill" type="button" data-bs-toggle="collapse" data-bs-target="#mobile-edit-product-{{ $item->id }}">Edit</button>
                            <form method="post" action="{{ route('products.destroy',$item) }}" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $item->name }}"><i class="bi bi-trash"></i></button></form>
                        </div>
                        <div class="collapse mt-3" id="mobile-stock-product-{{ $item->id }}">
                            <form method="post" action="{{ route('products.stock.update', $item) }}" class="row g-2 align-items-end">@csrf
                                <div class="col-12"><label class="form-label">Movement</label><select class="form-select" name="type"><option>Add</option><option>Remove</option><option>Set</option></select></div>
                                <div class="col-12"><label class="form-label">Quantity ({{ $item->stock_unit ?: 'pcs' }})</label><input class="form-control" name="quantity" type="number" min="0" step="0.001" inputmode="decimal" required></div>
                                <div class="col-12"><label class="form-label">Notes</label><input class="form-control" name="notes" placeholder="Reason"></div>
                                <div class="col-12"><button class="btn btn-success btn-sm w-100">Update Stock</button></div>
                            </form>
                        </div>
                        <div class="collapse mt-3" id="mobile-edit-product-{{ $item->id }}">
                            <form method="post" action="{{ route('products.update',$item) }}" class="row g-2">@csrf @method('PUT')
                                @include('products.partials.fields', ['product' => $item])
                                <div class="col-12"><button class="btn btn-warning btn-sm w-100">Update Product</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No products yet.</div>
                @endforelse
            </div>
            <div class="table-responsive d-none d-lg-block">
            <table class="table align-middle">
                <thead><tr><th>Name</th><th>Category</th><th>SKU</th><th>Price</th><th>Cost</th><th>Stock</th><th>Reorder</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($products as $item)
                    <tr>
                        <td>{{ $item->name }}<div class="small text-muted">{{ $item->description }}</div></td>
                        <td>{{ $item->category?->name }}</td>
                        <td>{{ $item->sku }}</td>
                        <td>{{ number_format($item->price,2) }}</td>
                        <td>{{ number_format($item->cost_price,2) }}</td>
                        <td>
                            <strong>{{ $item->formattedStock() }}</strong>
                            @if($item->isLowStock())<div class="small text-danger fw-bold">Low stock</div>@endif
                        </td>
                        <td>{{ $item->formattedStock((float) $item->reorder_level) }}</td>
                        <td><span class="status-pill">{{ $item->is_active ? 'active' : 'inactive' }}</span></td>
                        <td class="text-end d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#stock-product-{{ $item->id }}"><i class="bi bi-boxes"></i></button>
                            <button class="btn btn-sm btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#edit-product-{{ $item->id }}"><i class="bi bi-pencil"></i></button>
                            <form method="post" action="{{ route('products.destroy',$item) }}" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                    <tr class="collapse" id="stock-product-{{ $item->id }}"><td colspan="9">
                        <form method="post" action="{{ route('products.stock.update', $item) }}" class="row g-2 align-items-end">@csrf
                            <div class="col-md-3"><label class="form-label">Movement</label><select class="form-select" name="type"><option>Add</option><option>Remove</option><option>Set</option></select></div>
                            <div class="col-md-3"><label class="form-label">Quantity ({{ $item->stock_unit ?: 'pcs' }})</label><input class="form-control" name="quantity" type="number" min="0" step="0.001" required></div>
                            <div class="col-md-4"><label class="form-label">Notes</label><input class="form-control" name="notes" placeholder="Reason"></div>
                            <div class="col-md-2"><button class="btn btn-success btn-sm w-100">Update Stock</button></div>
                        </form>
                    </td></tr>
                    <tr class="collapse" id="edit-product-{{ $item->id }}"><td colspan="9">
                        <form method="post" action="{{ route('products.update',$item) }}" class="row g-2">@csrf @method('PUT')
                            @include('products.partials.fields', ['product' => $item])
                            <div class="col-12"><button class="btn btn-warning btn-sm">Update Product</button></div>
                        </form>
                    </td></tr>
                @empty <tr><td colspan="9" class="text-muted">No products yet.</td></tr>@endforelse
                </tbody>
            </table>
            </div>
            {{ $products->links() }}
        </div></div>
    </div>
    <div class="col-lg-4 order-1 order-lg-2">
        <div class="card mb-4" id="add-product"><div class="card-body">
            <h2 class="h5">Add Product</h2>
            <form method="post" action="{{ route('products.store') }}" class="row g-2">@csrf
                @include('products.partials.fields', ['product' => $product])
                <div class="col-12"><button class="btn btn-warning w-100">Save Product</button></div>
            </form>
        </div></div>
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5">Import / Export Products</h2>
            <form method="post" action="{{ route('products.import') }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-12"><input class="form-control" type="file" name="product_file" accept=".csv,.txt,.tsv,.xls,.xlsx" required></div>
                <div class="col-12"><button class="btn btn-outline-warning w-100"><i class="bi bi-upload me-1"></i>Upload CSV / Excel</button></div>
            </form>
            <div class="d-flex gap-2 mt-3">
                <a class="btn btn-sm btn-outline-dark flex-fill" href="{{ route('products.export', ['format' => 'csv']) }}"><i class="bi bi-download me-1"></i>CSV</a>
                <a class="btn btn-sm btn-outline-dark flex-fill" href="{{ route('products.export', ['format' => 'xls']) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>
            </div>
            <div class="small text-muted mt-2">Columns: name, sku, category, description, price, cost_price, stock_quantity, reorder_level, stock_unit, is_active.</div>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 class="h5">Add Category</h2>
            <form method="post" action="{{ route('product-categories.store') }}">@csrf
                <input class="form-control mb-2" name="name" placeholder="Category name" required>
                <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
                <button class="btn btn-outline-warning btn-sm">Save Category</button>
            </form>
            <hr>
            @foreach($categories as $category)<div class="border-top py-2">{{ $category->name }}<div class="small text-muted">{{ $category->description }}</div></div>@endforeach
        </div></div>
        <div class="card mt-4"><div class="card-body">
            <h2 class="h5">Recent Stock Movements</h2>
            @forelse($stockMovements as $movement)
                <div class="border-top py-2">
                    <strong>{{ $movement->product?->name }}</strong>
                    <span class="float-end">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->product?->formattedStock((float) $movement->quantity) ?? number_format($movement->quantity, 3) }}</span>
                    <div class="small text-muted">{{ $movement->type }} · Balance {{ $movement->product?->formattedStock((float) $movement->balance_after) ?? number_format($movement->balance_after, 3) }} · {{ $movement->created_at?->format('d M Y H:i') }}</div>
                </div>
            @empty
                <div class="text-muted">No stock movements yet.</div>
            @endforelse
        </div></div>
    </div>
</div>
@endsection
