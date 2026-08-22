<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionSite extends ConstructionModel
{
    protected $table = 'construction_sites';

    protected $casts = ['meta' => 'array', 'start_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function siteManager() { return $this->belongsTo(User::class, 'site_manager_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_id'); }
}
