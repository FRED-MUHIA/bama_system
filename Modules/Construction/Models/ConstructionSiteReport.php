<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionSiteReport extends ConstructionModel
{
    protected $table = 'construction_site_reports';

    protected $casts = ['photos' => 'array', 'report_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function preparedBy() { return $this->belongsTo(User::class, 'prepared_by'); }
}
