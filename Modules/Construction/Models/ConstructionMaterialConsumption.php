<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionMaterialConsumption extends ConstructionModel
{
    protected $table = 'construction_material_consumptions';

    protected $casts = ['usage_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function material() { return $this->belongsTo(ConstructionMaterial::class, 'material_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_id'); }
}
