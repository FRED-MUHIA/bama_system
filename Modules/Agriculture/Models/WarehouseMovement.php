<?php

namespace Modules\Agriculture\Models;

class WarehouseMovement extends AgricultureModel
{
    protected $table = 'agriculture_warehouse_movements';
    protected $casts = ['movement_date' => 'date', 'quantity' => 'decimal:3', 'loss_quantity' => 'decimal:3'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function produceBatch() { return $this->belongsTo(ProduceBatch::class, 'produce_batch_id'); }
    public function warehouse() { return $this->belongsTo(ProduceWarehouse::class, 'warehouse_id'); }
    public function storageBin() { return $this->belongsTo(StorageBin::class, 'storage_bin_id'); }
}
