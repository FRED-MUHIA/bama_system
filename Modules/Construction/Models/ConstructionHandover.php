<?php

namespace Modules\Construction\Models;

use App\Models\Project;

class ConstructionHandover extends ConstructionModel
{
    protected $table = 'construction_handovers';

    protected $casts = [
        'checklist' => 'array',
        'practical_completion_date' => 'date',
        'handover_date' => 'date',
        'dlp_start_date' => 'date',
        'dlp_end_date' => 'date',
    ];

    public function project() { return $this->belongsTo(Project::class); }
}
