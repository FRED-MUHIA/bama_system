<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockService
{
    public function receive(Product $product, float $quantity, ?Model $source = null, ?string $reference = null, ?string $notes = null): Product
    {
        return $this->move($product, abs($quantity), 'Received', $source, $reference, $notes);
    }

    public function consume(Product $product, float $quantity, ?Model $source = null, ?string $reference = null, ?string $notes = null): Product
    {
        return $this->move($product, -abs($quantity), 'Consumed', $source, $reference, $notes);
    }

    public function adjust(Product $product, float $quantity, string $type, ?string $notes = null): Product
    {
        $quantity = match ($type) {
            'Set' => $quantity - (float) $product->stock_quantity,
            'Remove' => -abs($quantity),
            default => abs($quantity),
        };

        return $this->move($product, $quantity, $type, null, 'Manual stock update', $notes);
    }

    public function syncSaleItems(Collection $oldItems, Collection $newItems, ?Model $source = null, ?string $reference = null): void
    {
        $old = $this->productQuantities($oldItems);
        $new = $this->productQuantities($newItems);

        $old->keys()
            ->merge($new->keys())
            ->unique()
            ->each(function ($productId) use ($old, $new, $source, $reference) {
                $delta = (float) ($new->get($productId, 0) - $old->get($productId, 0));
                if ($delta == 0.0) {
                    return;
                }

                $product = Product::find($productId);
                if (! $product) {
                    return;
                }

                $delta > 0
                    ? $this->consume($product, $delta, $source, $reference, 'Stock consumed by sale.')
                    : $this->receive($product, abs($delta), $source, $reference, 'Stock restored after sale update.');
            });
    }

    private function move(Product $product, float $quantity, string $type, ?Model $source = null, ?string $reference = null, ?string $notes = null): Product
    {
        if ($quantity == 0.0) {
            return $product;
        }

        return DB::transaction(function () use ($product, $quantity, $type, $source, $reference, $notes) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $balance = max((float) $product->stock_quantity + $quantity, 0);
            $product->update(['stock_quantity' => round($balance, 3)]);

            if (Schema::hasTable('stock_movements')) {
                $product->stockMovements()->create([
                    'type' => $type,
                    'quantity' => $quantity,
                    'balance_after' => $balance,
                    'source_type' => $source ? $source::class : null,
                    'source_id' => $source?->getKey(),
                    'reference' => $reference,
                    'notes' => $notes,
                    'created_by' => auth()->id(),
                ]);
            }

            return $product->refresh();
        });
    }

    private function productQuantities(Collection $items): Collection
    {
        return $items
            ->filter(fn ($item) => ! empty(data_get($item, 'product_id')))
            ->groupBy(fn ($item) => (int) data_get($item, 'product_id'))
            ->map(fn ($group) => $group->sum(fn ($item) => (float) data_get($item, 'quantity', 0)));
    }
}
