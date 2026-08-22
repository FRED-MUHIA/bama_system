<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionMaterialRequest extends ConstructionModel
{
    protected $table = 'construction_material_requests';

    protected $casts = ['required_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function material() { return $this->belongsTo(ConstructionMaterial::class, 'material_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
}
