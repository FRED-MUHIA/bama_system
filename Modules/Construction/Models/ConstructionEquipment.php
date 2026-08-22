<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionEquipment extends ConstructionModel
{
    protected $table = 'construction_equipment';

    protected $casts = ['next_service_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function operator() { return $this->belongsTo(User::class, 'operator_id'); }
}
