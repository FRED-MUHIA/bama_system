<?php

namespace Modules\Retail\Services;

use App\Models\CategoryAttribute;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Services\IamService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Retail\Models\RetailProductBundle;
use Modules\Retail\Models\RetailProductProfile;
use Modules\Retail\Models\RetailProductVariant;
use Modules\Retail\Models\ProductVariantAttributeValue;

class RetailCatalogService
{
    public const DEFAULT_MAX_VARIANTS = 250;

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

    public function suggestedAttributes(ProductCategory $category): Collection
    {
        $categoryIds = $this->categoryPathIds($category);

        return CategoryAttribute::with('attribute.values')
            ->whereIn('product_category_id', $categoryIds)
            ->get()
            ->sortBy(fn (CategoryAttribute $template) => (array_search($template->product_category_id, $categoryIds, true) * 1000) + $template->sort_order)
            ->unique('product_attribute_id')
            ->values();
    }

    public function assignCategoryTemplateAttributes(Product $product): Collection
    {
        if (! $product->category) {
            return collect();
        }

        return $this->suggestedAttributes($product->category)
            ->map(function (CategoryAttribute $template) use ($product) {
                return $product->attributeAssignments()->updateOrCreate(
                    ['product_attribute_id' => $template->product_attribute_id],
                    [
                        'is_variant_attribute' => $template->is_variant_attribute,
                        'sort_order' => $template->sort_order,
                        'metadata' => ['source' => 'category_template'],
                    ]
                );
            });
    }

    public function createVariant(Product $parent, Product $variant, array $attributes = []): RetailProductVariant
    {
        return RetailProductVariant::create([
            'parent_product_id' => $parent->id,
            'product_id' => $variant->id,
            'variant_name' => $attributes['variant_name'] ?? null,
            'sku' => $variant->sku,
            'barcode' => $attributes['barcode'] ?? $variant->barcode,
            'combination_key' => $attributes['combination_key'] ?? null,
            'attributes' => $attributes['attributes'] ?? $attributes,
            'cost_price' => $attributes['cost_price'] ?? $variant->cost_price,
            'retail_price' => $attributes['retail_price'] ?? $variant->price,
            'wholesale_price' => $attributes['wholesale_price'] ?? $variant->wholesale_price,
            'promotional_price' => $attributes['promotional_price'] ?? $variant->promotional_price,
            'tax_class' => $attributes['tax_class'] ?? $variant->tax_class,
            'status' => 'Active',
            'is_active' => true,
            'track_inventory' => $attributes['track_inventory'] ?? true,
        ]);
    }

    public function generateVariants(Product $parent, array $attributeValueIdsByAttributeId, array $options = []): Collection
    {
        $sets = $this->normalizeVariantValueSets($attributeValueIdsByAttributeId);

        if ($sets === []) {
            return collect();
        }

        $maxVariants = (int) ($options['max_variants'] ?? self::DEFAULT_MAX_VARIANTS);
        $requested = array_product(array_map('count', $sets));

        if ($requested > $maxVariants) {
            throw new InvalidArgumentException("Variant generation would create {$requested} variants. Limit is {$maxVariants}.");
        }

        $attributes = ProductAttribute::with('values')->whereIn('id', array_keys($sets))->get()->keyBy('id');
        $values = ProductAttributeValue::with('attribute')->whereIn('id', collect($sets)->flatten()->all())->get()->keyBy('id');

        $this->assertValidVariantSets($sets, $attributes, $values);

        return DB::transaction(function () use ($parent, $sets, $attributes, $values, $options) {
            if (($parent->product_type ?? 'simple') !== 'variable') {
                $parent->forceFill(['product_type' => 'variable'])->save();
            }

            return collect($this->cartesian($sets))
                ->map(function (array $combination, int $index) use ($parent, $attributes, $values, $options) {
                    $combinationKey = self::combinationKey($combination);
                    $existing = RetailProductVariant::with('attributeValueLinks.value.attribute')
                        ->where('parent_product_id', $parent->id)
                        ->where('combination_key', $combinationKey)
                        ->first();

                    if ($existing) {
                        return $existing;
                    }

                    $links = collect($combination)->map(fn (int $valueId, int $attributeId) => [
                        'attribute' => $attributes->get($attributeId),
                        'value' => $values->get($valueId),
                    ])->values();

                    $variantName = $this->variantName($links);
                    $sku = $this->uniqueSku($this->formatSku(
                        $parent,
                        $links,
                        $options['sku_pattern'] ?? 'PRODUCT-SEQUENCE',
                        $index + 1
                    ));

                    $price = data_get($options, "pricing.{$combinationKey}.retail_price", $parent->price);
                    $cost = data_get($options, "pricing.{$combinationKey}.cost_price", $parent->cost_price);

                    $variantProduct = Product::create([
                        'product_category_id' => $parent->product_category_id,
                        'product_brand_id' => $parent->product_brand_id,
                        'product_type' => 'simple',
                        'name' => $parent->name.' - '.$variantName,
                        'sku' => $sku,
                        'barcode' => data_get($options, "barcodes.{$combinationKey}"),
                        'description' => $parent->description,
                        'price' => $price,
                        'cost_price' => $cost,
                        'wholesale_price' => data_get($options, "pricing.{$combinationKey}.wholesale_price", $parent->wholesale_price),
                        'promotional_price' => data_get($options, "pricing.{$combinationKey}.promotional_price", $parent->promotional_price),
                        'tax_class' => data_get($options, "pricing.{$combinationKey}.tax_class", $parent->tax_class),
                        'stock_quantity' => 0,
                        'reorder_level' => $parent->reorder_level ?? 0,
                        'stock_unit' => $parent->stock_unit ?: 'pcs',
                        'is_active' => true,
                        'status' => 'active',
                        'track_inventory' => true,
                    ]);

                    $variant = $this->createVariant($parent, $variantProduct, [
                        'variant_name' => $variantName,
                        'barcode' => $variantProduct->barcode,
                        'combination_key' => $combinationKey,
                        'attributes' => $this->attributePayload($links),
                        'cost_price' => $variantProduct->cost_price,
                        'retail_price' => $variantProduct->price,
                        'wholesale_price' => $variantProduct->wholesale_price,
                        'promotional_price' => $variantProduct->promotional_price,
                        'tax_class' => $variantProduct->tax_class,
                    ]);

                    $links->each(function (array $link) use ($variant) {
                        ProductVariantAttributeValue::create([
                            'variant_id' => $variant->id,
                            'product_attribute_id' => $link['attribute']->id,
                            'product_attribute_value_id' => $link['value']->id,
                        ]);
                    });

                    app(IamService::class)->audit('retail.catalog.variant.generated', $variant);

                    return $variant->load('attributeValueLinks.value.attribute');
                });
        });
    }

    public function attachBundleComponent(Product $bundle, Product $component, float $quantity, ?float $unitCost = null): RetailProductBundle
    {
        return RetailProductBundle::updateOrCreate(
            ['bundle_product_id' => $bundle->id, 'component_product_id' => $component->id],
            ['quantity' => $quantity, 'unit_cost' => $unitCost ?? $component->cost_price ?? 0]
        );
    }

    public static function combinationKey(array $combination): string
    {
        ksort($combination, SORT_NUMERIC);

        return collect($combination)
            ->map(fn ($valueId, $attributeId) => ((int) $attributeId).':'.((int) $valueId))
            ->implode('|');
    }

    private function categoryPathIds(ProductCategory $category): array
    {
        $ids = [];

        while ($category) {
            array_unshift($ids, $category->id);
            $category = $category->parent;
        }

        return $ids;
    }

    private function normalizeVariantValueSets(array $sets): array
    {
        return collect($sets)
            ->mapWithKeys(fn ($valueIds, $attributeId) => [
                (int) $attributeId => collect((array) $valueIds)
                    ->filter(fn ($valueId) => $valueId !== null && $valueId !== '')
                    ->map(fn ($valueId) => (int) $valueId)
                    ->unique()
                    ->values()
                    ->all(),
            ])
            ->filter()
            ->all();
    }

    private function assertValidVariantSets(array $sets, Collection $attributes, Collection $values): void
    {
        foreach ($sets as $attributeId => $valueIds) {
            if (! $attributes->has($attributeId)) {
                throw new InvalidArgumentException("Unknown product attribute {$attributeId}.");
            }

            foreach ($valueIds as $valueId) {
                $value = $values->get($valueId);

                if (! $value || (int) $value->product_attribute_id !== (int) $attributeId) {
                    throw new InvalidArgumentException("Attribute value {$valueId} does not belong to attribute {$attributeId}.");
                }
            }
        }
    }

    private function cartesian(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $attributeId => $valueIds) {
            $append = [];

            foreach ($result as $product) {
                foreach ($valueIds as $valueId) {
                    $append[] = $product + [(int) $attributeId => (int) $valueId];
                }
            }

            $result = $append;
        }

        return $result;
    }

    private function variantName(Collection $links): string
    {
        return $links->pluck('value.value')->implode(' / ');
    }

    private function attributePayload(Collection $links): array
    {
        return $links
            ->mapWithKeys(fn (array $link) => [$link['attribute']->code => $link['value']->value])
            ->all();
    }

    private function formatSku(Product $parent, Collection $links, string $pattern, int $sequence): string
    {
        $tokens = [
            'PRODUCT' => $this->skuSegment($parent->sku ?: $parent->name),
            'CATEGORY' => $this->skuSegment($parent->category?->code ?: $parent->category?->name ?: 'CAT'),
            'BRAND' => $this->skuSegment($parent->brand?->code ?: $parent->brand?->name ?: 'GEN'),
            'SEQUENCE' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
        ];

        foreach ($links as $link) {
            $tokens[Str::upper($link['attribute']->code)] = $this->skuSegment($link['value']->code ?: $link['value']->value);
            $tokens[Str::upper(Str::slug($link['attribute']->name, '_'))] = $this->skuSegment($link['value']->code ?: $link['value']->value);
        }

        $parts = collect(explode('-', Str::upper($pattern)))
            ->map(fn (string $token) => $tokens[$token] ?? $this->skuSegment($token))
            ->filter()
            ->values();

        return $parts->isEmpty() ? $tokens['PRODUCT'].'-'.$tokens['SEQUENCE'] : $parts->implode('-');
    }

    private function skuSegment(?string $value): string
    {
        return Str::upper(Str::slug((string) $value, '-')) ?: 'ITEM';
    }

    private function uniqueSku(string $base): string
    {
        $candidate = $base;
        $suffix = 2;

        while (
            Product::where('sku', $candidate)->exists()
            || RetailProductVariant::where('sku', $candidate)->exists()
        ) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
