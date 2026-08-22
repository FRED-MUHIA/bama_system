<?php

namespace Modules\Automotive\Models;

class SpecialtyRecord extends AutomotiveModel
{
    protected $table = 'automotive_specialty_records';

    protected $casts = ['payload' => 'array'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function jobCard() { return $this->belongsTo(JobCard::class); }
}
