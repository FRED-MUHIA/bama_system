<?php

namespace Modules\PrintingBranding\Models;

class Dispatch extends PrintingBrandingModel
{
    protected $table = 'printing_dispatches';

    protected $casts = [
        'dispatch_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
