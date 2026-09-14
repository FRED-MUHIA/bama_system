<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    protected $casts = [
        'base_ratio' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function packagingUnits()
    {
        return $this->hasMany(ProductPackagingUnit::class);
    }
}
