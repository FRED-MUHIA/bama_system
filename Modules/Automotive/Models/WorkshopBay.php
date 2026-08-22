<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class WorkshopBay extends AutomotiveModel
{
    protected $table = 'automotive_workshop_bays';

    protected $casts = ['meta' => 'array'];

    public function assignedTechnician() { return $this->belongsTo(User::class, 'assigned_technician_id'); }
    public function assignedJobCard() { return $this->belongsTo(JobCard::class, 'assigned_job_card_id'); }
}
