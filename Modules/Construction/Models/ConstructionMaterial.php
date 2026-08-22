<?php

namespace Modules\Construction\Models;

use App\Models\Product;
use App\Models\Supplier;

class ConstructionMaterial extends ConstructionModel
{
    protected $table = 'construction_materials';

    protected $casts = ['meta' => 'array'];

    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
