<?php

namespace Modules\Retail\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Modules\Retail\Models\ProductBatch;

class ProductIdentificationService
{
    public function lookup(string $identifierType, string $identifierValue): ?Product
    {
        return Product::query()
            ->with('category', 'retailProfile', 'retailInventoryBalances')
            ->where(function (Builder $query) use ($identifierType, $identifierValue) {
                $query->where('sku', $identifierValue)
                    ->orWhere('id', is_numeric($identifierValue) ? (int) $identifierValue : 0)
                    ->orWhereHas('retailProfile', function (Builder $profile) use ($identifierType, $identifierValue) {
                        $profile->where('barcode', $identifierValue)
                            ->orWhereJsonContains('attributes->'.$identifierType, $identifierValue)
                            ->orWhereJsonContains('attributes->gtin', $identifierValue)
                            ->orWhereJsonContains('attributes->upc', $identifierValue)
                            ->orWhereJsonContains('attributes->ean', $identifierValue)
                            ->orWhereJsonContains('attributes->qr_product_code', $identifierValue)
                            ->orWhereJsonContains('attributes->internal_product_number', $identifierValue);
                    });
            })
            ->first();
    }

    public function batchFor(Product $product, array $payload): ?ProductBatch
    {
        $batchNumber = $payload['batch_number'] ?? $payload['batch'] ?? null;
        if (! $batchNumber) {
            return null;
        }

        return ProductBatch::where('product_id', $product->id)->where('batch_number', $batchNumber)->first();
    }

    public function response(Product $product, array $promotionPayload = []): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'image' => data_get($product->retailProfile, 'images.0'),
            'description' => $product->description,
            'current_price' => (float) $product->price,
            'tax_category' => $product->retailProfile?->tax_class,
            'promotions' => $promotionPayload,
            'inventory_availability' => [
                'shared_stock' => (float) $product->stock_quantity,
                'retail_available' => $product->retailInventoryBalances->sum(fn ($balance) => (float) $balance->available_stock),
                'retail_reserved' => $product->retailInventoryBalances->sum(fn ($balance) => (float) $balance->reserved_stock),
            ],
        ];
    }
}
