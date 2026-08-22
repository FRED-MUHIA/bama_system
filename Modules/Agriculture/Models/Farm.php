<?php

namespace Modules\Agriculture\Models;

use App\Models\CostCenter;
use App\Models\User;

class Farm extends AgricultureModel
{
    protected $table = 'agriculture_farms';
    protected $casts = ['total_area' => 'decimal:4'];

    public function manager() { return $this->belongsTo(User::class, 'manager_id'); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function branches() { return $this->hasMany(FarmBranch::class, 'farm_id'); }
    public function fields() { return $this->hasMany(Field::class, 'farm_id'); }
    public function cropPlans() { return $this->hasMany(CropPlan::class, 'farm_id'); }
    public function animals() { return $this->hasMany(Animal::class, 'farm_id'); }
    public function equipment() { return $this->hasMany(Equipment::class, 'farm_id'); }
}
