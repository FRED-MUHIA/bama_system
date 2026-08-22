<?php

namespace Modules\Agriculture\Models;

class FarmZone extends AgricultureModel
{
    protected $table = 'agriculture_farm_zones';

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function branch() { return $this->belongsTo(FarmBranch::class, 'agriculture_farm_branch_id'); }
}
