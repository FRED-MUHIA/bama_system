<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionSiteDiary extends ConstructionModel
{
    protected $table = 'construction_site_diaries';

    protected $casts = ['attachments' => 'array', 'event_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
