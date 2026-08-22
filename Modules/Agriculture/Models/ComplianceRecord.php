<?php

namespace Modules\Agriculture\Models;

class ComplianceRecord extends AgricultureModel
{
    protected $table = 'agriculture_compliance_records';
    protected $casts = ['issue_date' => 'date', 'expiry_date' => 'date'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
}
