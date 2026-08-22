<?php

namespace Modules\Agriculture\Models;

class Harvest extends AgricultureModel
{
    protected $table = 'agriculture_harvests';
    protected $casts = ['harvest_date' => 'date', 'quantity' => 'decimal:3', 'waste_quantity' => 'decimal:3', 'expected_yield' => 'decimal:3', 'value' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function cropPlan() { return $this->belongsTo(CropPlan::class, 'crop_plan_id'); }
    public function crop() { return $this->belongsTo(Crop::class, 'crop_id'); }
    public function produceBatches() { return $this->hasMany(ProduceBatch::class, 'harvest_id'); }
}
