<?php

namespace Modules\Retail\Services;

use App\Models\Product;
use App\Services\IamService;
use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\RetailProductBundle;
use Modules\Retail\Models\RetailProductProfile;
use Modules\Retail\Models\RetailProductVariant;

class RetailCatalogService
{
    public function upsertProfile(Product $product, array $data): RetailProductProfile
    {
        return DB::transaction(function () use ($product, $data) {
            $profile = RetailProductProfile::updateOrCreate(
                ['product_id' => $product->id],
                $data + ['status' => $data['status'] ?? ($product->is_active ? 'Active' : 'Inactive')]
            );

            app(IamService::class)->audit('retail.catalog.profile.saved', $profile);

            return $profile->load('product', 'supplier');
        });
    }

    public function createVariant(Product $parent, Product $variant, array $attributes = []): RetailProductVariant
    {
        return RetailProductVariant::create([
            'parent_product_id' => $parent->id,
            'product_id' => $variant->id,
            'sku' => $variant->sku,
            'barcode' => $attributes['barcode'] ?? null,
            'attributes' => $attributes,
            'status' => 'Active',
        ]);
    }

    public function attachBundleComponent(Product $bundle, Product $component, float $quantity, ?float $unitCost = null): RetailProductBundle
    {
        return RetailProductBundle::updateOrCreate(
            ['bundle_product_id' => $bundle->id, 'component_product_id' => $component->id],
            ['quantity' => $quantity, 'unit_cost' => $unitCost ?? $component->cost_price ?? 0]
        );
    }
}
