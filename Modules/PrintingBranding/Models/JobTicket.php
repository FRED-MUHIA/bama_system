<?php

namespace Modules\PrintingBranding\Models;

class JobTicket extends PrintingBrandingModel
{
    protected $table = 'printing_job_tickets';

    protected $casts = ['ticket_data' => 'array', 'printed_at' => 'datetime'];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
