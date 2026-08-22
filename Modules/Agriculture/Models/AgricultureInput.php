<?php

namespace Modules\Agriculture\Models;

use App\Models\Product;
use App\Models\Supplier;

class AgricultureInput extends AgricultureModel
{
    protected $table = 'agriculture_inputs';
    protected $casts = ['expiry_date' => 'date', 'application_rate' => 'decimal:3', 'quantity_on_hand' => 'decimal:3', 'unit_cost' => 'decimal:2', 'reorder_level' => 'decimal:3'];

    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function usages() { return $this->hasMany(InputUsage::class, 'input_id'); }
}
