<?php

namespace Modules\Construction\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

class ConstructionEstimate extends ConstructionModel
{
    protected $table = 'construction_estimates';

    protected $casts = ['meta' => 'array'];

    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function boq() { return $this->belongsTo(ConstructionBoq::class, 'boq_id'); }
    public function estimator() { return $this->belongsTo(User::class, 'estimator_id'); }
}
