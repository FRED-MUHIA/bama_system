<?php

namespace Modules\Agriculture\Models;

class FarmActivity extends AgricultureModel
{
    protected $table = 'agriculture_farm_activities';
    protected $casts = ['scheduled_date' => 'date', 'completion_date' => 'date', 'inputs_used' => 'array', 'cost' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function cropPlan() { return $this->belongsTo(CropPlan::class, 'crop_plan_id'); }
    public function worker() { return $this->belongsTo(FarmWorker::class, 'assigned_worker_id'); }
    public function equipment() { return $this->belongsTo(Equipment::class, 'equipment_id'); }
}
