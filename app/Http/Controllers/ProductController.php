<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Services\ProductCatalogImportService;
use App\Services\StockService;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', [
            'products' => Product::with('category')->latest()->paginate(12),
            'categories' => ProductCategory::orderBy('name')->get(),
            'product' => new Product(['is_active' => true]),
            'stockUnits' => Product::STOCK_UNITS,
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

        return back()->with('status', 'Product saved.');
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
        $product->delete();

        return back()->with('status', 'Product deleted.');
    }

    public function storeCategory(Request $request)
    {
        ProductCategory::create($request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_categories', 'name')->where('business_id', ActiveBusiness::id())],
            'description' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Category saved.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $id = $product?->id ?? 'NULL';

        return $request->validate([
            'product_category_id' => ['nullable', Rule::exists('product_categories', 'id')->where('business_id', ActiveBusiness::id())],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($id)->where('business_id', ActiveBusiness::id())],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'stock_unit' => ['required', Rule::in(array_keys(Product::STOCK_UNITS))],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
