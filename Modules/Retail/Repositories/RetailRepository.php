<?php

namespace Modules\Retail\Repositories;

use App\Models\Client;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Modules\Retail\Models\RetailGiftCard;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailPromotion;
use Modules\Retail\Models\RetailReturnAuthorization;
use Modules\Retail\Models\RetailWarehouse;

class RetailRepository
{
    public function productSearch(?string $term = null): Builder
    {
        return Product::query()
            ->with('category', 'brand', 'retailVariants.attributeValueLinks.value.attribute')
            ->where('is_active', true)
            ->when($term, function (Builder $query) use ($term) {
                collect(preg_split('/\s+/', trim($term)) ?: [])
                    ->filter()
                    ->take(8)
                    ->each(function (string $part) use ($query) {
                        $like = "%{$part}%";

                        $query->where(function (Builder $query) use ($like) {
                            $query->where('name', 'like', $like)
                                ->orWhere('sku', 'like', $like)
                                ->orWhere('barcode', 'like', $like)
                                ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', $like)->orWhere('code', 'like', $like))
                                ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $like)->orWhere('code', 'like', $like))
                                ->orWhereHas('retailProfile', fn (Builder $profile) => $profile->where('barcode', 'like', $like)->orWhere('brand', 'like', $like))
                                ->orWhereHas('retailVariants', fn (Builder $variant) => $variant->where('sku', 'like', $like)->orWhere('barcode', 'like', $like)->orWhere('variant_name', 'like', $like))
                                ->orWhereHas('retailVariants.attributeValueLinks.value', fn (Builder $value) => $value->where('value', 'like', $like)->orWhere('code', 'like', $like));
                        });
                    });
            })
            ->orderBy('name');
    }

    public function dashboardSales()
    {
        return PosOrder::query()->with('items.product', 'client', 'paymentMethod');
    }

    public function customers()
    {
        return Client::query()
            ->with('retailProfile')
            ->withCount(['posOrders', 'retailOrders'])
            ->withSum('posOrders as pos_order_total', 'total')
            ->withSum('retailOrders as retail_order_total', 'total');
    }

    public function suppliers()
    {
        return Supplier::query()->with('retailProfile');
    }

    public function inventoryBalances()
    {
        return RetailInventoryBalance::query()->with('product', 'variant.product', 'branch', 'warehouse', 'bin');
    }

    public function warehouses()
    {
        return RetailWarehouse::query()->with('branch', 'zones', 'bins');
    }

    public function promotions()
    {
        return RetailPromotion::query()->latest();
    }

    public function giftCards()
    {
        return RetailGiftCard::query()->with('client')->latest();
    }

    public function returns()
    {
        return RetailReturnAuthorization::query()->with('order', 'client', 'items.product')->latest();
    }

    public function orders()
    {
        return RetailOrder::query()->with('client', 'branch', 'items.product', 'delivery')->latest();
    }
}
