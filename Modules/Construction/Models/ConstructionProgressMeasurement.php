<?php

namespace Modules\Construction\Models;

use App\Models\Project;
use App\Models\User;

class ConstructionProgressMeasurement extends ConstructionModel
{
    protected $table = 'construction_progress_measurements';

    protected $casts = ['photos' => 'array', 'measurement_date' => 'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(ConstructionSite::class, 'site_id'); }
    public function boqItem() { return $this->belongsTo(ConstructionBoqItem::class, 'boq_item_id'); }
    public function measuredBy() { return $this->belongsTo(User::class, 'measured_by'); }
}
