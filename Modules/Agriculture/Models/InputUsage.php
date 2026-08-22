<?php

namespace Modules\Agriculture\Models;

class InputUsage extends AgricultureModel
{
    protected $table = 'agriculture_input_usages';
    protected $casts = ['usage_date' => 'date', 'quantity_used' => 'decimal:3', 'cost' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function input() { return $this->belongsTo(AgricultureInput::class, 'input_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function cropPlan() { return $this->belongsTo(CropPlan::class, 'crop_plan_id'); }
    public function activity() { return $this->belongsTo(FarmActivity::class, 'activity_id'); }
    public function worker() { return $this->belongsTo(FarmWorker::class, 'worker_id'); }
}
