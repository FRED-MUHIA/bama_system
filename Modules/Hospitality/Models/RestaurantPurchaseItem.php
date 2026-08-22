<?php

namespace Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantPurchaseItem extends Model
{
    protected $table = 'hospitality_restaurant_purchase_items';

    protected $fillable = ['restaurant_purchase_id', 'ingredient_id', 'description', 'quantity', 'unit_cost', 'line_total'];

    protected $casts = ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(RestaurantPurchase::class, 'restaurant_purchase_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
