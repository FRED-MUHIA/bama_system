<?php

namespace Modules\Retail\Models;

class RetailWarehouseZone extends RetailModel
{
    public function warehouse() { return $this->belongsTo(RetailWarehouse::class, 'retail_warehouse_id'); }
    public function bins() { return $this->hasMany(RetailWarehouseBin::class); }
}
