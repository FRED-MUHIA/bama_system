<?php

namespace Modules\PrintingBranding\Models;

class JobCost extends PrintingBrandingModel
{
    protected $table = 'printing_job_costs';

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
