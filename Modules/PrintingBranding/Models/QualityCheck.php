<?php

namespace Modules\PrintingBranding\Models;

class QualityCheck extends PrintingBrandingModel
{
    protected $table = 'printing_quality_checks';

    protected $casts = [
        'inspection_date' => 'datetime',
        'checkpoints' => 'array',
        'photos' => 'array',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
