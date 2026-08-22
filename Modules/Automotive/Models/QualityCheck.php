<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class QualityCheck extends AutomotiveModel
{
    protected $table = 'automotive_quality_checks';

    protected $casts = ['checklist' => 'array', 'inspected_at' => 'datetime'];

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function inspector() { return $this->belongsTo(User::class, 'inspector_id'); }
}
