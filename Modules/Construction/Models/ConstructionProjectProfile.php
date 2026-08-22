<?php

namespace Modules\Construction\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

class ConstructionProjectProfile extends ConstructionModel
{
    protected $table = 'construction_project_profiles';

    protected $casts = ['meta' => 'array', 'start_date' => 'date', 'planned_completion' => 'date', 'actual_completion' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function projectManager() { return $this->belongsTo(User::class, 'project_manager_id'); }
    public function siteManager() { return $this->belongsTo(User::class, 'site_manager_id'); }
}
