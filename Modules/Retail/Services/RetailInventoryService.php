<?php

namespace Modules\Retail\Services;

use App\Models\Product;
use App\Services\IamService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailInventoryMovement;
use Modules\Retail\Models\RetailProductVariant;

class RetailInventoryService
{
    public function __construct(private StockService $stock) {}

    public function receive(Product $product, float $quantity, array $context = [], ?Model $source = null): RetailInventoryBalance
    {
        $this->stock->receive($product, $quantity, $source, $context['reference'] ?? 'Retail receiving', $context['notes'] ?? null);

        return $this->move($product, $quantity, 'Received', 'available_stock', $context, $source);
    }

    public function receiveVariant(RetailProductVariant $variant, float $quantity, array $context = [], ?Model $source = null): RetailInventoryBalance
    {
        $this->stock->receive($variant->product, $quantity, $source, $context['reference'] ?? 'Retail variant receiving', $context['notes'] ?? null);

        return $this->move($variant->parentProduct, $quantity, 'Received', 'available_stock', $this->variantContext($variant, $context), $source);
    }

    public function reserve(Product $product, float $quantity, array $context = [], ?Model $source = null): RetailInventoryBalance
    {
        return DB::transaction(function () use ($product, $quantity, $context, $source) {
            $balance = $this->balance($product, $context, true);

            if ((float) $balance->available_stock < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Available stock is lower than the reservation quantity.']);
            }

            $balance->update([
                'available_stock' => (float) $balance->available_stock - $quantity,
                'reserved_stock' => (float) $balance->reserved_stock + $quantity,
            ]);

            $this->movement($product, $quantity, 'Reserved', $balance->reserved_stock, $context, $source);
            app(IamService::class)->audit('retail.inventory.reserved', $balance);

            return $balance->refresh();
        });
    }

    public function reserveVariant(RetailProductVariant $variant, float $quantity, array $context = [], ?Model $source = null): RetailInventoryBalance
    {
        return $this->reserve($variant->parentProduct, $quantity, $this->variantContext($variant, $context), $source);
    }

    public function adjust(Product $product, float $quantity, string $bucket = 'available_stock', array $context = [], ?Model $source = null): RetailInventoryBalance
    {
        $this->stock->adjust($product, abs($quantity), $quantity < 0 ? 'Remove' : 'Add', $context['notes'] ?? 'Retail inventory adjustment.');

        return $this->move($product, $quantity, 'Adjusted', $bucket, $context, $source);
    }

    public function adjustVariant(RetailProductVariant $variant, float $quantity, string $bucket = 'available_stock', array $context = [], ?Model $source = null): RetailInventoryBalance
    {
        $this->stock->adjust($variant->product, abs($quantity), $quantity < 0 ? 'Remove' : 'Add', $context['notes'] ?? 'Retail variant inventory adjustment.');

        return $this->move($variant->parentProduct, $quantity, 'Adjusted', $bucket, $this->variantContext($variant, $context), $source);
    }

    public function transfer(Product $product, float $quantity, array $from, array $to, ?Model $source = null): void
    {
        DB::transaction(function () use ($product, $quantity, $from, $to, $source) {
            $this->move($product, -abs($quantity), 'Transfer Out', 'available_stock', $from, $source);
            $this->move($product, abs($quantity), 'Transfer In', 'available_stock', $to, $source);
        });
    }

    public function transferVariant(RetailProductVariant $variant, float $quantity, array $from, array $to, ?Model $source = null): void
    {
        DB::transaction(function () use ($variant, $quantity, $from, $to, $source) {
            $this->move($variant->parentProduct, -abs($quantity), 'Transfer Out', 'available_stock', $this->variantContext($variant, $from), $source);
            $this->move($variant->parentProduct, abs($quantity), 'Transfer In', 'available_stock', $this->variantContext($variant, $to), $source);
        });
    }

    private function move(Product $product, float $quantity, string $type, string $bucket, array $context, ?Model $source): RetailInventoryBalance
    {
        return DB::transaction(function () use ($product, $quantity, $type, $bucket, $context, $source) {
            $balance = $this->balance($product, $context, true);
            $newBucketBalance = max((float) $balance->{$bucket} + $quantity, 0);
            $unitCost = (float) ($context['unit_cost'] ?? $balance->unit_cost ?? $product->cost_price ?? 0);
            $availableStock = $bucket === 'available_stock'
                ? $newBucketBalance
                : (float) $balance->available_stock;

            $balance->update([
                $bucket => $newBucketBalance,
                'unit_cost' => $unitCost,
                'stock_value' => $availableStock * $unitCost,
            ]);

            $this->movement($product, $quantity, $type, $newBucketBalance, $context, $source);
            app(IamService::class)->audit('retail.inventory.moved', $balance);

            return $balance->refresh();
        });
    }

    private function balance(Product $product, array $context, bool $lock = false): RetailInventoryBalance
    {
        $keys = [
            'product_id' => $product->id,
            'retail_product_variant_id' => $context['retail_product_variant_id'] ?? null,
            'branch_id' => $context['branch_id'] ?? null,
            'retail_warehouse_id' => $context['retail_warehouse_id'] ?? null,
            'retail_warehouse_bin_id' => $context['retail_warehouse_bin_id'] ?? null,
        ];

        $query = RetailInventoryBalance::query()->where($keys);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first() ?: RetailInventoryBalance::create($keys + [
            'valuation_method' => $context['valuation_method'] ?? 'FIFO',
            'reorder_level' => $context['reorder_level'] ?? $product->reorder_level ?? 0,
            'unit_cost' => $context['unit_cost'] ?? $product->cost_price ?? 0,
        ]);
    }

    private function movement(Product $product, float $quantity, string $type, float $balanceAfter, array $context, ?Model $source): RetailInventoryMovement
    {
        return RetailInventoryMovement::create([
            'product_id' => $product->id,
            'retail_product_variant_id' => $context['retail_product_variant_id'] ?? null,
            'branch_id' => $context['branch_id'] ?? null,
            'retail_warehouse_id' => $context['retail_warehouse_id'] ?? null,
            'retail_warehouse_bin_id' => $context['retail_warehouse_bin_id'] ?? null,
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $context['unit_cost'] ?? $product->cost_price ?? 0,
            'balance_after' => $balanceAfter,
            'reference' => $context['reference'] ?? null,
            'notes' => $context['notes'] ?? null,
            'source_type' => $source ? $source::class : null,
            'source_id' => $source?->getKey(),
            'metadata' => $context['metadata'] ?? null,
        ]);
    }

    private function variantContext(RetailProductVariant $variant, array $context): array
    {
        $context['retail_product_variant_id'] = $variant->id;
        $context['unit_cost'] ??= $variant->cost_price ?? $variant->product?->cost_price;

        return $context;
    }
}
