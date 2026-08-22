<?php

namespace Modules\Retail\Models;

use App\Models\Branch;

class ScanDevice extends RetailModel
{
    protected $casts = ['capabilities' => 'array', 'last_seen_at' => 'datetime'];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function events() { return $this->hasMany(ScanEvent::class); }
}
