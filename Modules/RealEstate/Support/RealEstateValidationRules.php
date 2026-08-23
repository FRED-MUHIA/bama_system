<?php

namespace Modules\RealEstate\Support;

use App\Support\ActiveBusiness;
use Illuminate\Validation\Rule;

class RealEstateValidationRules
{
    public static array $propertyTypes = ['Apartment', 'House', 'Villa', 'Office', 'Retail Shop', 'Warehouse', 'Mixed Use', 'Hotel', 'Hostel', 'Land', 'Industrial Property'];
    public static array $propertyStatuses = ['Available', 'Occupied', 'Reserved', 'Sold', 'Under Maintenance', 'Under Construction', 'Archived'];
    public static array $unitStatuses = ['Vacant', 'Occupied', 'Reserved', 'Sold', 'Maintenance'];

    public static function property(): array
    {
        return [
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'property_manager_id' => ['nullable', self::activeBusinessUserRule()],
            'property_code' => ['nullable', 'string', 'max:50'],
            'property_name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', Rule::in(self::$propertyTypes)],
            'ownership_type' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(self::$propertyStatuses)],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:100'],
            'county_state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'acquisition_date' => ['nullable', 'date'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public static function unit(): array
    {
        return [
            'real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'unit_number' => ['required', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'block' => ['nullable', 'string', 'max:50'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'square_footage' => ['nullable', 'numeric', 'min:0'],
            'occupancy_status' => ['required', Rule::in(self::$unitStatuses)],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public static function clientExtension(string $prefix): array
    {
        return [
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'name' => ['required_without:client_id', 'nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            "{$prefix}_number" => ['nullable', 'string', 'max:50'],
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
