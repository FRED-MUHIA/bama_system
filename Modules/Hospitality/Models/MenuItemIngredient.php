<?php

namespace Modules\Hospitality\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemIngredient extends HospitalityModel
{
    protected $table = 'hospitality_menu_item_ingredients';

    protected $fillable = ['tenant_id', 'business_id', 'product_id', 'ingredient_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
