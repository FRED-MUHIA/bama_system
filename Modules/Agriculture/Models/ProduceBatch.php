<?php

namespace Modules\Agriculture\Models;

use App\Models\Product;

class ProduceBatch extends AgricultureModel
{
    protected $table = 'agriculture_produce_batches';
    protected $casts = ['quantity' => 'decimal:3', 'date_received' => 'date', 'recommended_sale_date' => 'date'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function harvest() { return $this->belongsTo(Harvest::class, 'harvest_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function sales() { return $this->hasMany(ProduceSale::class, 'produce_batch_id'); }
}
