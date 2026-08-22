<?php

namespace Modules\Agriculture\Models;

class Crop extends AgricultureModel
{
    protected $table = 'agriculture_crops';
    protected $casts = ['expected_yield' => 'decimal:3', 'is_active' => 'boolean'];

    public function cropPlans() { return $this->hasMany(CropPlan::class, 'crop_id'); }
}
