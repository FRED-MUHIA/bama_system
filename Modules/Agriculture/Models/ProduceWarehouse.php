<?php

namespace Modules\Agriculture\Models;

class ProduceWarehouse extends AgricultureModel
{
    protected $table = 'agriculture_produce_warehouses';
    protected $casts = ['capacity' => 'decimal:3'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function bins() { return $this->hasMany(StorageBin::class, 'warehouse_id'); }
}
