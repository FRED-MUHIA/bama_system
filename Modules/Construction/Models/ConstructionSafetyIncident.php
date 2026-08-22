<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionSafetyIncident extends ConstructionModel
{
    protected $table = 'construction_safety_incidents';

    protected $casts = ['photos' => 'array', 'incident_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_id'); }
    public function contractor() { return $this->belongsTo(ConstructionContractor::class, 'contractor_id'); }
}
