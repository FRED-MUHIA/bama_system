<?php

namespace Modules\Agriculture\Models;

class StorageBin extends AgricultureModel
{
    protected $table = 'agriculture_storage_bins';
    protected $casts = ['capacity' => 'decimal:3', 'temperature_c' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function warehouse() { return $this->belongsTo(ProduceWarehouse::class, 'warehouse_id'); }
}
