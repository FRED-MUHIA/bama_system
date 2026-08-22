<?php

namespace Modules\PrintingBranding\Models;

class ProductionOperation extends PrintingBrandingModel
{
    protected $table = 'printing_operations';

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
        'material_used' => 'array',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
