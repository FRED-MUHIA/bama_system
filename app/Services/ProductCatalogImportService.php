<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Support\ActiveBusiness;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\RetailProductProfile;

class ProductCatalogImportService
{
    public const HEADERS = [
        'name',
        'sku',
        'product_type',
        'category',
        'subcategory',
        'brand',
        'barcode',
        'description',
        'price',
        'wholesale_price',
        'promotional_price',
        'cost_price',
        'tax_class',
        'stock_quantity',
        'reorder_level',
        'stock_unit',
        'is_active',
    ];

    public function __construct(private StockService $stock)
    {
    }

    public function import(UploadedFile $file): array
    {
        $rows = $this->rows($file);
        $created = $updated = $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row = $this->normalizeRow($row);

            if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
                $skipped++;
                continue;
            }

            if (empty($row['name'])) {
                $errors[] = "Line {$line}: name is required.";
                $skipped++;
                continue;
            }

            if (! is_numeric($row['price'] ?? null)) {
                $errors[] = "Line {$line}: price must be numeric.";
                $skipped++;
                continue;
            }

            $categoryId = $this->categoryId($row['category'] ?? null, $row['subcategory'] ?? null);
            $brandId = $this->brandId($row['brand'] ?? null);
            $stockQuantity = is_numeric($row['stock_quantity'] ?? null) ? (float) $row['stock_quantity'] : 0;
            $sku = trim((string) ($row['sku'] ?? ''));

            $attributes = [
                'product_category_id' => $categoryId,
                'product_brand_id' => $brandId,
                'product_type' => $this->productType($row['product_type'] ?? null),
                'name' => trim((string) $row['name']),
                'sku' => $sku !== '' ? $sku : null,
                'barcode' => $this->blankToNull($row['barcode'] ?? null),
                'description' => $row['description'] ?? null,
                'price' => (float) $row['price'],
                'wholesale_price' => is_numeric($row['wholesale_price'] ?? null) ? (float) $row['wholesale_price'] : null,
                'promotional_price' => is_numeric($row['promotional_price'] ?? null) ? (float) $row['promotional_price'] : null,
                'cost_price' => is_numeric($row['cost_price'] ?? null) ? (float) $row['cost_price'] : 0,
                'tax_class' => $this->blankToNull($row['tax_class'] ?? null),
                'reorder_level' => is_numeric($row['reorder_level'] ?? null) ? (float) $row['reorder_level'] : 0,
                'stock_unit' => $this->stockUnit($row['stock_unit'] ?? null),
                'is_active' => $this->boolean($row['is_active'] ?? true),
                'status' => $this->boolean($row['is_active'] ?? true) ? 'active' : 'inactive',
            ];

            $product = $sku !== ''
                ? Product::where('sku', $sku)->first()
                : Product::where('name', $attributes['name'])->first();

            if ($product) {
                $oldStock = (float) $product->stock_quantity;
                $product->update($attributes);
                if (abs($oldStock - $stockQuantity) > 0.0001) {
                    $this->stock->adjust($product, $stockQuantity, 'Set', 'Stock updated from product import.');
                }
                $this->syncRetailProfile($product);
                $updated++;
            } else {
                $product = Product::create($attributes + ['stock_quantity' => 0]);
                if ($stockQuantity > 0) {
                    $this->stock->adjust($product, $stockQuantity, 'Add', 'Opening stock from product import.');
                }
                $this->syncRetailProfile($product);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    public function exportRows()
    {
        return Product::with('category.parent', 'brand')->orderBy('name')->get()->map(fn (Product $product) => [
            'name' => $product->name,
            'sku' => $product->sku,
            'product_type' => $product->product_type ?: 'simple',
            'category' => $product->category?->parent?->name ?: $product->category?->name,
            'subcategory' => $product->category?->parent ? $product->category->name : null,
            'brand' => $product->brand?->name,
            'barcode' => $product->barcode,
            'description' => $product->description,
            'price' => (float) $product->price,
            'wholesale_price' => $product->wholesale_price !== null ? (float) $product->wholesale_price : null,
            'promotional_price' => $product->promotional_price !== null ? (float) $product->promotional_price : null,
            'cost_price' => (float) $product->cost_price,
            'tax_class' => $product->tax_class,
            'stock_quantity' => (float) $product->stock_quantity,
            'reorder_level' => (float) $product->reorder_level,
            'stock_unit' => $product->stock_unit ?: 'pcs',
            'is_active' => $product->is_active ? 1 : 0,
        ]);
    }

    private function rows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'xlsx') {
            throw ValidationException::withMessages([
                'product_file' => 'XLSX imports require the PHP Zip extension on this server. Save the spreadsheet as CSV or Excel 97-2003 tab-delimited XLS and upload again.',
            ]);
        }

        $content = file_get_contents($file->getRealPath());
        $delimiter = $this->delimiter($content, $extension);
        $handle = fopen($file->getRealPath(), 'rb');
        $header = null;
        $rows = [];

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($header === null) {
                $header = $this->normalizeHeader($line);
                continue;
            }

            $values = array_slice(array_pad($line, count($header), null), 0, count($header));
            $rows[] = array_combine($header, $values);
        }

        fclose($handle);

        return $rows;
    }

    private function delimiter(string $content, string $extension): string
    {
        if (in_array($extension, ['tsv', 'xls'], true)) {
            return "\t";
        }

        $firstLine = strtok($content, "\r\n") ?: '';

        return substr_count($firstLine, "\t") > substr_count($firstLine, ',') ? "\t" : ',';
    }

    private function normalizeHeader(array $header): array
    {
        return array_map(function ($value) {
            $key = strtolower(trim((string) $value));
            $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
            $key = str_replace([' ', '-'], '_', $key);

            return match ($key) {
                'product_name' => 'name',
                'type' => 'product_type',
                'item_type' => 'product_type',
                'category_name' => 'category',
                'sub_category', 'sub-category' => 'subcategory',
                'manufacturer' => 'brand',
                'brand_name' => 'brand',
                'gtin', 'ean', 'upc' => 'barcode',
                'selling_price' => 'price',
                'retail_price' => 'price',
                'wholesale' => 'wholesale_price',
                'promo_price' => 'promotional_price',
                'cost' => 'cost_price',
                'tax' => 'tax_class',
                'stock', 'quantity' => 'stock_quantity',
                'unit' => 'stock_unit',
                'active', 'status' => 'is_active',
                default => $key,
            };
        }, $header);
    }

    private function normalizeRow(array $row): array
    {
        return Arr::only($row, self::HEADERS);
    }

    private function categoryId(?string $name, ?string $subcategory = null): ?int
    {
        $segments = collect(preg_split('/\s*(?:>|\/)\s*/', trim((string) $name)) ?: [])
            ->filter()
            ->values();

        $subcategory = trim((string) $subcategory);
        if ($subcategory !== '') {
            $segments->push($subcategory);
        }

        if ($segments->isEmpty()) {
            return null;
        }

        $parentId = null;
        $category = null;

        foreach ($segments as $segment) {
            $category = ProductCategory::firstOrCreate(
                ['name' => $segment],
                [
                    'parent_id' => $parentId,
                    'code' => Str::upper(Str::slug($segment, '-')),
                    'slug' => Str::slug($segment),
                    'description' => 'Created from product import.',
                    'business_id' => ActiveBusiness::id(),
                ]
            );

            if ($parentId && (int) $category->parent_id !== (int) $parentId) {
                $category->update(['parent_id' => $parentId]);
            }

            $parentId = $category->id;
        }

        return $category?->id;
    }

    private function brandId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return ProductBrand::firstOrCreate(
            ['name' => $name],
            [
                'code' => Str::upper(Str::slug($name, '-')),
                'slug' => Str::slug($name),
                'status' => 'Active',
                'business_id' => ActiveBusiness::id(),
            ]
        )->id;
    }

    private function productType(?string $type): string
    {
        $type = strtolower(trim((string) $type));
        $type = str_replace([' ', '_'], '-', $type);

        return match ($type) {
            'variable', 'variable-product' => 'variable',
            'service', 'service-product' => 'service',
            'bundle', 'kit', 'bundle-kit', 'bundle-/-kit' => 'bundle',
            'composite', 'composite-product' => 'composite',
            'digital', 'digital-product' => 'digital',
            default => 'simple',
        };
    }

    private function stockUnit(?string $unit): string
    {
        $unit = strtolower(trim((string) $unit));

        return array_key_exists($unit, Product::STOCK_UNITS) ? $unit : 'pcs';
    }

    private function boolean(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y', 'active', 'enabled'], true);
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function syncRetailProfile(Product $product): void
    {
        if (! Schema::hasTable('retail_product_profiles')) {
            return;
        }

        RetailProductProfile::updateOrCreate(
            ['product_id' => $product->id],
            [
                'product_brand_id' => $product->product_brand_id,
                'barcode' => $product->barcode,
                'brand' => $product->brand?->name,
                'tax_class' => $product->tax_class,
                'product_type' => $product->productTypeLabel(),
                'status' => $product->is_active ? 'Active' : 'Inactive',
            ]
        );
    }
}
