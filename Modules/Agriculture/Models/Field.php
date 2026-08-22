<?php

namespace Modules\Agriculture\Models;

class Field extends AgricultureModel
{
    protected $table = 'agriculture_fields';
    protected $casts = ['size' => 'decimal:4', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function branch() { return $this->belongsTo(FarmBranch::class, 'agriculture_farm_branch_id'); }
    public function zone() { return $this->belongsTo(FarmZone::class, 'agriculture_farm_zone_id'); }
    public function cropPlans() { return $this->hasMany(CropPlan::class, 'field_id'); }
    public function plots() { return $this->hasMany(Plot::class, 'field_id'); }
}
