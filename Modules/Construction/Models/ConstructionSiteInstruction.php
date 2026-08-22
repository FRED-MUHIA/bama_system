<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionSiteInstruction extends ConstructionModel
{
    protected $table = 'construction_site_instructions';

    protected $casts = ['photos' => 'array', 'instruction_date' => 'date', 'due_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function issuer() { return $this->belongsTo(User::class, 'issuer_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'recipient_id'); }
}
