<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ActiveBusiness;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ProductCatalogImportService
{
    public const HEADERS = [
        'name',
        'sku',
        'category',
        'description',
        'price',
        'cost_price',
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

            $categoryId = $this->categoryId($row['category'] ?? null);
            $stockQuantity = is_numeric($row['stock_quantity'] ?? null) ? (float) $row['stock_quantity'] : 0;
            $sku = trim((string) ($row['sku'] ?? ''));

            $attributes = [
                'product_category_id' => $categoryId,
                'name' => trim((string) $row['name']),
                'sku' => $sku !== '' ? $sku : null,
                'description' => $row['description'] ?? null,
                'price' => (float) $row['price'],
                'cost_price' => is_numeric($row['cost_price'] ?? null) ? (float) $row['cost_price'] : 0,
                'reorder_level' => is_numeric($row['reorder_level'] ?? null) ? (float) $row['reorder_level'] : 0,
                'stock_unit' => $this->stockUnit($row['stock_unit'] ?? null),
                'is_active' => $this->boolean($row['is_active'] ?? true),
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
                $updated++;
            } else {
                $product = Product::create($attributes + ['stock_quantity' => 0]);
                if ($stockQuantity > 0) {
                    $this->stock->adjust($product, $stockQuantity, 'Add', 'Opening stock from product import.');
                }
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    public function exportRows()
    {
        return Product::with('category')->orderBy('name')->get()->map(fn (Product $product) => [
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category?->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'cost_price' => (float) $product->cost_price,
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
                'category_name' => 'category',
                'selling_price' => 'price',
                'cost' => 'cost_price',
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

    private function categoryId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return ProductCategory::firstOrCreate(
            ['name' => $name],
            ['description' => 'Created from product import.', 'business_id' => ActiveBusiness::id()]
        )->id;
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
}
