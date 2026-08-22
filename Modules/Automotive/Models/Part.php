<?php

namespace Modules\Automotive\Models;

use App\Models\Product;
use App\Models\Supplier;

class Part extends AutomotiveModel
{
    protected $table = 'automotive_parts';

    protected $casts = ['vehicle_compatibility' => 'array', 'meta' => 'array'];

    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function compatibilities() { return $this->hasMany(PartCompatibility::class); }
}
