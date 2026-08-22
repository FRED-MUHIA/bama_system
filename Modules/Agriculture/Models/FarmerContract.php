<?php

namespace Modules\Agriculture\Models;

class FarmerContract extends AgricultureModel
{
    protected $table = 'agriculture_farmer_contracts';
    protected $casts = ['acreage' => 'decimal:4', 'inputs_provided' => 'array', 'expected_quantity' => 'decimal:3', 'agreed_price' => 'decimal:2', 'delivery_dates' => 'array'];

    public function farmer() { return $this->belongsTo(Farmer::class, 'farmer_id'); }
    public function crop() { return $this->belongsTo(Crop::class, 'crop_id'); }
}
