<?php

namespace Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingredient extends HospitalityModel
{
    protected $table = 'hospitality_ingredients';

    protected $fillable = ['tenant_id', 'business_id', 'unit_id', 'name', 'sku', 'on_hand', 'reorder_level', 'cost_per_unit', 'is_active'];

    protected $casts = ['on_hand' => 'decimal:3', 'reorder_level' => 'decimal:3', 'cost_per_unit' => 'decimal:2', 'is_active' => 'boolean'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
