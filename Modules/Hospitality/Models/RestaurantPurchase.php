<?php

namespace Modules\Hospitality\Models;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantPurchase extends HospitalityModel
{
    protected $table = 'hospitality_restaurant_purchases';

    protected $fillable = ['tenant_id', 'business_id', 'purchase_number', 'supplier_id', 'supplier_name', 'status', 'shipping_method', 'expected_at', 'total', 'notes'];

    protected $casts = ['expected_at' => 'date', 'total' => 'decimal:2'];

    public const STATUSES = ['Draft', 'Ordered', 'Received', 'Cancelled'];
    public const SHIPPING_METHODS = ['Supplier Delivery', 'Pickup', 'Courier', 'Internal Transfer'];

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantPurchaseItem::class, 'restaurant_purchase_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
