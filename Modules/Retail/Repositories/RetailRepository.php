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
            ->with('category')
            ->where('is_active', true)
            ->when($term, function (Builder $query) use ($term) {
                $query->where(function (Builder $query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhereHas('retailProfile', fn (Builder $profile) => $profile->where('barcode', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%"));
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
        return Client::query()->with('retailProfile', 'posOrders');
    }

    public function suppliers()
    {
        return Supplier::query()->with('retailProfile');
    }

    public function inventoryBalances()
    {
        return RetailInventoryBalance::query()->with('product', 'branch', 'warehouse', 'bin');
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
