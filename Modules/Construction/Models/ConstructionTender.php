<?php

namespace Modules\Construction\Models;

use App\Models\Client;
use App\Models\Project;

class ConstructionTender extends ConstructionModel
{
    protected $table = 'construction_tenders';

    protected $casts = ['documents' => 'array', 'submission_date' => 'date', 'closing_date' => 'date', 'site_visit_date' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function boq() { return $this->belongsTo(ConstructionBoq::class, 'boq_id'); }
    public function estimate() { return $this->belongsTo(ConstructionEstimate::class, 'estimate_id'); }
    public function checklist() { return $this->hasMany(ConstructionTenderChecklistItem::class, 'tender_id'); }
}
