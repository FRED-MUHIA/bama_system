<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Collection;
use Modules\Retail\Models\RetailPromotion;

class RetailPromotionService
{
    public function activeFor(?Client $client = null, ?int $branchId = null): Collection
    {
        return RetailPromotion::where('status', 'Active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->get()
            ->filter(fn (RetailPromotion $promotion) => $this->eligible($promotion, $client, $branchId))
            ->values();
    }

    public function discountFor(Product $product, float $lineTotal, ?Client $client = null, ?int $branchId = null): float
    {
        $promotion = $this->activeFor($client, $branchId)
            ->first(fn (RetailPromotion $promotion) => $this->productEligible($promotion, $product));

        if (! $promotion) {
            return 0;
        }

        return match ($promotion->promotion_type) {
            'Percentage Discount', 'Flash Sale', 'Happy Hour Pricing', 'Seasonal Promotion' => round($lineTotal * ((float) $promotion->discount_value / 100), 2),
            'Fixed Discount', 'Bundle Discount' => min(round((float) $promotion->discount_value, 2), $lineTotal),
            default => 0,
        };
    }

    private function eligible(RetailPromotion $promotion, ?Client $client, ?int $branchId): bool
    {
        $stores = collect($promotion->store_eligibility ?: []);
        if ($stores->isNotEmpty() && $branchId && ! $stores->contains($branchId)) {
            return false;
        }

        $customers = collect($promotion->customer_eligibility ?: []);
        if ($customers->isNotEmpty() && $client && ! $customers->contains($client->id)) {
            return false;
        }

        return true;
    }

    private function productEligible(RetailPromotion $promotion, Product $product): bool
    {
        $products = collect($promotion->product_eligibility ?: []);

        return $products->isEmpty() || $products->contains($product->id) || $products->contains($product->sku);
    }
}
