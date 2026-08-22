<?php

namespace Modules\Automotive\Models;

class EstimateItem extends AutomotiveModel
{
    protected $table = 'automotive_estimate_items';

    public function estimate() { return $this->belongsTo(Estimate::class); }
    public function part() { return $this->belongsTo(Part::class); }
    public function labourOperation() { return $this->belongsTo(LabourOperation::class); }
}
