<?php

namespace Modules\Retail\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Collection;
use Modules\Retail\Models\RetailEcommerceIntegration;

class RetailEcommerceCatalogService
{
    public function products(RetailEcommerceIntegration $integration, array $filters = []): Collection
    {
        return Product::withoutGlobalScopes()
            ->with('category', 'retailProfile', 'retailInventoryBalances')
            ->where('business_id', $integration->business_id)
            ->where('is_active', true)
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhereHas('retailProfile', fn ($profile) => $profile->where('barcode', 'like', "%{$term}%"));
                });
            })
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('product_category_id', $categoryId))
            ->when($filters['updated_since'] ?? null, fn ($query, $date) => $query->where('updated_at', '>=', $date))
            ->orderBy('name')
            ->limit((int) ($filters['limit'] ?? 100))
            ->get()
            ->map(fn (Product $product) => $this->productPayload($product, $integration));
    }

    public function categories(RetailEcommerceIntegration $integration): Collection
    {
        return ProductCategory::withoutGlobalScopes()
            ->where('business_id', $integration->business_id)
            ->whereHas('products', fn ($query) => $query->withoutGlobalScopes()->where('business_id', $integration->business_id)->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description ?? null,
            ]);
    }

    public function pricing(RetailEcommerceIntegration $integration): Collection
    {
        return $this->products($integration)->map(fn (array $product) => [
            'id' => $product['id'],
            'sku' => $product['sku'],
            'name' => $product['name'],
            'pricing' => $product['pricing'],
            'tax' => $product['tax'],
            'inventory' => $product['inventory'],
        ]);
    }

    private function productPayload(Product $product, RetailEcommerceIntegration $integration): array
    {
        $profile = $product->retailProfile;
        $currency = data_get($profile, 'currency_prices.default.currency', data_get($integration->settings, 'currency', 'KES'));

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'barcode' => $profile?->barcode,
            'name' => $product->name,
            'description' => $product->description,
            'status' => $profile?->status ?? ($product->is_active ? 'Active' : 'Inactive'),
            'type' => $profile?->product_type ?? 'Physical Product',
            'brand' => $profile?->brand,
            'category' => [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
            ],
            'pricing' => [
                'currency' => $currency,
                'selling_price' => (float) $product->price,
                'cost_price' => (float) $product->cost_price,
                'multi_currency' => $profile?->currency_prices ?? [],
            ],
            'tax' => [
                'class' => $profile?->tax_class,
            ],
            'inventory' => [
                'stock_unit' => $product->stock_unit,
                'shared_stock' => (float) $product->stock_quantity,
                'available_stock' => $product->retailInventoryBalances->sum(fn ($balance) => (float) $balance->available_stock) ?: (float) $product->stock_quantity,
                'reserved_stock' => $product->retailInventoryBalances->sum(fn ($balance) => (float) $balance->reserved_stock),
            ],
            'images' => $profile?->images ?? [],
            'attributes' => $profile?->attributes ?? [],
            'tags' => $profile?->tags ?? [],
            'updated_at' => optional($product->updated_at)->toISOString(),
        ];
    }
}
