<?php

namespace Modules\PrintingBranding\Models;

class Reprint extends PrintingBrandingModel
{
    protected $table = 'printing_reprints';

    public function originalJob()
    {
        return $this->belongsTo(ProductionJob::class, 'original_job_id');
    }
}
