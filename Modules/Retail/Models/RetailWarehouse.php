<?php

namespace Modules\Retail\Models;

use App\Models\Branch;

class RetailWarehouse extends RetailModel
{
    protected $casts = ['capacity' => 'decimal:3', 'metadata' => 'array'];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function zones() { return $this->hasMany(RetailWarehouseZone::class); }
    public function bins() { return $this->hasMany(RetailWarehouseBin::class); }
}
