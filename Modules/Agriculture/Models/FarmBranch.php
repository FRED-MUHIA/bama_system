<?php

namespace Modules\Agriculture\Models;

use App\Models\Branch;
use App\Models\User;

class FarmBranch extends AgricultureModel
{
    protected $table = 'agriculture_farm_branches';

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function sharedBranch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function manager() { return $this->belongsTo(User::class, 'manager_id'); }
}
