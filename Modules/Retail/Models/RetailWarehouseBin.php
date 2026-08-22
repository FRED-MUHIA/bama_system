<?php

namespace Modules\Retail\Models;

class RetailWarehouseBin extends RetailModel
{
    protected $casts = ['capacity' => 'decimal:3'];

    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function zone() { return $this->belongsTo(RetailWarehouseZone::class, 'retail_warehouse_zone_id'); }
}
