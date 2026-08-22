<?php

namespace Modules\Construction\Models;

use App\Models\Project;

class ConstructionSubcontract extends ConstructionModel
{
    protected $table = 'construction_subcontracts';

    protected $casts = ['boq_item_ids' => 'array', 'start_date' => 'date', 'completion_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function contractor() { return $this->belongsTo(ConstructionContractor::class, 'contractor_id'); }
}
