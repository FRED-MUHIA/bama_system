<?php

namespace Modules\Agriculture\Models;

use App\Models\User;

class CropPlan extends AgricultureModel
{
    protected $table = 'agriculture_crop_plans';
    protected $casts = ['planting_date' => 'date', 'expected_germination_date' => 'date', 'expected_harvest_date' => 'date', 'planned_acreage' => 'decimal:4', 'seed_quantity' => 'decimal:3', 'expected_yield' => 'decimal:3', 'budget' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function season() { return $this->belongsTo(FarmSeason::class, 'season_id'); }
    public function crop() { return $this->belongsTo(Crop::class, 'crop_id'); }
    public function manager() { return $this->belongsTo(User::class, 'manager_id'); }
    public function activities() { return $this->hasMany(FarmActivity::class, 'crop_plan_id'); }
    public function harvests() { return $this->hasMany(Harvest::class, 'crop_plan_id'); }
}
