<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionRfi extends ConstructionModel
{
    protected $table = 'construction_rfis';

    protected $casts = ['attachments' => 'array', 'required_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function raisedBy() { return $this->belongsTo(User::class, 'raised_by'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
}
