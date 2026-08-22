<?php

namespace Modules\Agriculture\Models;

class BudgetLine extends AgricultureModel
{
    protected $table = 'agriculture_budget_lines';
    protected $casts = ['budget_amount' => 'decimal:2', 'actual_amount' => 'decimal:2', 'alert_threshold' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function cropPlan() { return $this->belongsTo(CropPlan::class, 'crop_plan_id'); }
    public function animal() { return $this->belongsTo(Animal::class, 'animal_id'); }
    public function equipment() { return $this->belongsTo(Equipment::class, 'equipment_id'); }
}
