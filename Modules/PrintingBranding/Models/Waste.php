<?php

namespace Modules\PrintingBranding\Models;

class Waste extends PrintingBrandingModel
{
    protected $table = 'printing_wastes';

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
