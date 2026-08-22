<?php

namespace Modules\PrintingBranding\Models;

class ProductionSchedule extends PrintingBrandingModel
{
    protected $table = 'printing_schedules';

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
