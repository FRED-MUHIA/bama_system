<?php

namespace Modules\Retail\Services;

use App\Support\ActiveBusiness;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailDelivery;
use Modules\Retail\Models\RetailLoyaltyAccount;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailPromotion;

class RetailValidationRules
{
    public static function productProfile(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'barcode' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('business_id', ActiveBusiness::id())],
            'tax_class' => ['nullable', 'string', 'max:100'],
            'product_type' => ['required', Rule::in(['Physical Product', 'Digital Product', 'Service Product', 'Gift Card', 'Bundle', 'Kit'])],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Discontinued'])],
            'attributes' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ];
    }

    public static function promotion(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'promotion_type' => ['required', Rule::in(RetailPromotion::TYPES)],
            'code' => ['nullable', 'string', 'max:100'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['Draft', 'Active', 'Paused', 'Expired'])],
            'product_eligibility' => ['nullable', 'array'],
            'customer_eligibility' => ['nullable', 'array'],
            'store_eligibility' => ['nullable', 'array'],
        ];
    }

    public static function loyalty(): array
    {
        return [
            'client_id' => ['required', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'tier' => ['required', Rule::in(RetailLoyaltyAccount::TIERS)],
            'points_balance' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public static function order(): array
    {
        return [
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'channel' => ['required', Rule::in(['Store', 'Online Store', 'Mobile Commerce', 'Marketplace', 'Special Order'])],
            'status' => ['required', Rule::in(RetailOrder::STATUSES)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public static function delivery(): array
    {
        return [
            'retail_order_id' => ['required', Rule::exists('retail_orders', 'id')->where('business_id', ActiveBusiness::id())],
            'driver_id' => ['nullable', self::activeBusinessUserRule()],
            'status' => ['required', Rule::in(RetailDelivery::STATUSES)],
            'scheduled_at' => ['nullable', 'date'],
            'delivery_address' => ['required', 'string'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    private static function activeBusinessUserRule()
    {
        return Rule::exists('users', 'id')
            ->where(fn ($query) => $query->whereIn('id', function ($subquery) {
                $subquery->select('user_id')
                    ->from('business_user')
                    ->where('business_id', ActiveBusiness::id())
                    ->whereIn('status', ['Active', 'Pending Invitation']);
            }));
    }
}
