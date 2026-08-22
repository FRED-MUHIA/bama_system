<?php

namespace Modules\Automotive\Models;

class JobCost extends AutomotiveModel
{
    protected $table = 'automotive_job_costs';

    public function jobCard() { return $this->belongsTo(JobCard::class); }
}
