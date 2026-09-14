<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Services\ProductCatalogImportService;
use App\Services\StockService;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\RetailProductProfile;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', [
            'products' => Product::with('category.parent', 'brand', 'retailVariants')->latest()->paginate(12),
            'categories' => ProductCategory::with('parent')->orderBy('sort_order')->orderBy('name')->get(),
            'brands' => ProductBrand::orderBy('name')->get(),
            'attributes' => ProductAttribute::with('values')->orderBy('sort_order')->orderBy('name')->get(),
            'product' => new Product(['is_active' => true]),
            'stockUnits' => Product::STOCK_UNITS,
            'productTypes' => Product::PRODUCT_TYPES,
            'lowStockProducts' => Product::where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level')->orderBy('stock_quantity')->get(),
            'stockMovements' => StockMovement::with('product')->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $openingStock = (float) ($data['stock_quantity'] ?? 0);
        $data['stock_quantity'] = 0;
        $product = Product::create($data);

        if ($openingStock > 0) {
            app(StockService::class)->adjust($product, $openingStock, 'Add', 'Opening stock.');
        }

        $this->syncRetailProfile($product, $data);

        return back()->with('status', 'Product saved.')->with('open_product_form', true);
    }

    public function update(Request $request, Product $product)
    {
        $oldStock = (float) $product->stock_quantity;
        $data = $this->validated($request, $product);
        $newStock = (float) ($data['stock_quantity'] ?? 0);
        unset($data['stock_quantity']);
        $product->update($data);

        if (abs($oldStock - $newStock) > 0.0001) {
            app(StockService::class)->adjust($product, $newStock, 'Set', 'Stock updated from product edit.');
        }

        $this->syncRetailProfile($product, $data);

        return back()->with('status', 'Product updated.');
    }

    public function updateStock(Request $request, Product $product, StockService $stock)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['Add', 'Remove', 'Set'])],
            'quantity' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $stock->adjust($product, (float) $data['quantity'], $data['type'], $data['notes'] ?? null);

        return back()->with('status', 'Stock updated for '.$product->name.'.');
    }

    public function import(Request $request, ProductCatalogImportService $importer)
    {
        $data = $request->validate([
            'product_file' => ['required', 'file', 'max:10240'],
        ]);

        $extension = strtolower($data['product_file']->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'txt', 'tsv', 'xls', 'xlsx'], true)) {
            throw ValidationException::withMessages([
                'product_file' => 'Upload a CSV, TXT, TSV, XLS, or XLSX product file.',
            ]);
        }

        $summary = $importer->import($data['product_file']);
        $message = "Product import complete: {$summary['created']} created, {$summary['updated']} updated, {$summary['skipped']} skipped.";

        if ($summary['errors']) {
            return back()->with('warning', $message.' '.implode(' ', array_slice($summary['errors'], 0, 5)));
        }

        return back()->with('status', $message);
    }

    public function export(Request $request, ProductCatalogImportService $catalog)
    {
        $format = $request->query('format', 'csv') === 'xls' ? 'xls' : 'csv';
        $filename = 'product-catalog-'.now()->format('Ymd-His').'.'.$format;
        $delimiter = $format === 'xls' ? "\t" : ',';
        $contentType = $format === 'xls' ? 'application/vnd.ms-excel' : 'text/csv';

        return response()->streamDownload(function () use ($catalog, $delimiter) {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ProductCatalogImportService::HEADERS, $delimiter);

            foreach ($catalog->exportRows() as $row) {
                fputcsv($out, $row, $delimiter);
            }

            fclose($out);
        }, $filename, ['Content-Type' => $contentType]);
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false, 'status' => 'archived']);

        return back()->with('status', 'Product archived.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', Rule::exists('product_categories', 'id')->where('business_id', ActiveBusiness::id())],
            'name' => ['required', 'string', 'max:255', Rule::unique('product_categories', 'name')->where('business_id', ActiveBusiness::id())],
            'code' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = $data['code'] ?: Str::upper(Str::slug($data['name'], '-'));
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        ProductCategory::create(collect($data)->reject(fn ($value) => $value === null)->all());

        return back()->with('status', 'Category saved.');
    }

    public function storeBrand(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_brands', 'name')->where('business_id', ActiveBusiness::id())],
            'code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $data['code'] = $data['code'] ?: Str::upper(Str::slug($data['name'], '-'));
        $data['slug'] = Str::slug($data['name']);

        ProductBrand::create(collect($data)->reject(fn ($value) => $value === null)->all());

        return back()->with('status', 'Brand saved.');
    }

    public function storeAttribute(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('product_attributes', 'code')->where('business_id', ActiveBusiness::id())],
            'display_type' => ['required', Rule::in(ProductAttribute::DISPLAY_TYPES)],
            'unit' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_variant_attribute' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = $data['code'] ?: Str::slug($data['name'], '_');
        $data['is_variant_attribute'] = $request->boolean('is_variant_attribute');
        $data['is_filterable'] = $request->boolean('is_filterable');
        $data['is_searchable'] = $request->boolean('is_searchable');
        $data['is_active'] = $request->boolean('is_active', true);

        ProductAttribute::create(collect($data)->reject(fn ($value) => $value === null)->all());

        return back()->with('status', 'Attribute saved.');
    }

    public function storeAttributeValue(Request $request, ProductAttribute $attribute)
    {
        $data = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'numeric_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'hex_color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = $data['code'] ?: Str::upper(Str::slug($data['value'], '-'));
        $data['is_active'] = $request->boolean('is_active', true);

        $attribute->values()->create(collect($data)->reject(fn ($value) => $value === null)->all());

        return back()->with('status', 'Attribute value saved.');
    }

    public function storeCategoryAttribute(Request $request, ProductCategory $category)
    {
        $data = $request->validate([
            'product_attribute_id' => ['required', Rule::exists('product_attributes', 'id')->where('business_id', ActiveBusiness::id())],
            'is_required' => ['nullable', 'boolean'],
            'is_variant_attribute' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->attributeTemplates()->updateOrCreate(
            ['product_attribute_id' => $data['product_attribute_id']],
            [
                'is_required' => $request->boolean('is_required'),
                'is_variant_attribute' => $request->boolean('is_variant_attribute'),
                'sort_order' => $data['sort_order'] ?? 0,
            ]
        );

        return back()->with('status', 'Category attribute saved.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $id = $product?->id ?? 'NULL';

        return $request->validate([
            'product_category_id' => ['nullable', Rule::exists('product_categories', 'id')->where('business_id', ActiveBusiness::id())],
            'product_brand_id' => ['nullable', Rule::exists('product_brands', 'id')->where('business_id', ActiveBusiness::id())],
            'product_type' => ['nullable', Rule::in(array_keys(Product::PRODUCT_TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($id)->where('business_id', ActiveBusiness::id())],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'promotional_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'tax_class' => ['nullable', 'string', 'max:100'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'stock_unit' => ['required', Rule::in(array_keys(Product::STOCK_UNITS))],
            'status' => ['nullable', Rule::in(['draft', 'active', 'inactive', 'discontinued', 'archived'])],
            'track_inventory' => ['nullable', 'boolean'],
            'is_serialized' => ['nullable', 'boolean'],
            'is_batch_tracked' => ['nullable', 'boolean'],
            'is_expiry_tracked' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'product_type' => 'simple',
            'status' => $request->boolean('is_active') ? 'active' : 'inactive',
            'track_inventory' => $request->boolean('track_inventory', true),
            'is_serialized' => $request->boolean('is_serialized'),
            'is_batch_tracked' => $request->boolean('is_batch_tracked'),
            'is_expiry_tracked' => $request->boolean('is_expiry_tracked'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function syncRetailProfile(Product $product, array $data): void
    {
        if (! Schema::hasTable('retail_product_profiles')) {
            return;
        }

        $brand = $product->brand;

        RetailProductProfile::updateOrCreate(
            ['product_id' => $product->id],
            [
                'product_brand_id' => $product->product_brand_id,
                'barcode' => $data['barcode'] ?? $product->barcode,
                'brand' => $brand?->name,
                'tax_class' => $data['tax_class'] ?? $product->tax_class,
                'product_type' => $product->productTypeLabel(),
                'status' => $product->is_active ? 'Active' : 'Inactive',
            ]
        );
    }
}
