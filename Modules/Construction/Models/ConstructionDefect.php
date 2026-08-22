<?php

namespace Modules\Construction\Models;

use App\Models\Project;

class ConstructionDefect extends ConstructionModel
{
    protected $table = 'construction_defects';

    protected $casts = ['photos' => 'array', 'reported_date' => 'date', 'target_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function contractor() { return $this->belongsTo(ConstructionContractor::class, 'contractor_id'); }
}
