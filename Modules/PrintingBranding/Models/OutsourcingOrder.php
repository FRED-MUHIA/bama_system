<?php

namespace Modules\PrintingBranding\Models;

class OutsourcingOrder extends PrintingBrandingModel
{
    protected $table = 'printing_outsourcing_orders';

    protected $casts = ['expected_completion' => 'date'];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }
}
