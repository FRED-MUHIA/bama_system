<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class LabourTask extends AutomotiveModel
{
    protected $table = 'automotive_labour_tasks';

    protected $casts = ['started_at' => 'datetime', 'paused_at' => 'datetime', 'completed_at' => 'datetime'];

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function labourOperation() { return $this->belongsTo(LabourOperation::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
}
