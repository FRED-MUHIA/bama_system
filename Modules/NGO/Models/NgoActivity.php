<?php

namespace Modules\NGO\Models;

use App\Models\Project;

class NgoActivity extends NgoModel
{
    protected $table = 'ngo_activities';
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'budget' => 'decimal:2'];
    public function program() { return $this->belongsTo(NgoProgram::class, 'program_id'); }
    public function project() { return $this->belongsTo(Project::class); }
}
