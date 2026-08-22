<?php

namespace Modules\Construction\Models;

use App\Models\Client;
use App\Models\Project;

class ConstructionVariation extends ConstructionModel
{
    protected $table = 'construction_variations';

    protected $casts = ['boq_changes' => 'array', 'submitted_date' => 'date', 'approved_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function client() { return $this->belongsTo(Client::class); }
}
