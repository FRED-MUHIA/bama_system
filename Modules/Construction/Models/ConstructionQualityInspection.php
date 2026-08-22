<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionQualityInspection extends ConstructionModel
{
    protected $table = 'construction_quality_inspections';

    protected $casts = ['photos' => 'array', 'inspection_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function inspector() { return $this->belongsTo(User::class, 'inspector_id'); }
}
