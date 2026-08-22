<?php

namespace Modules\Automotive\Models;

class Warranty extends AutomotiveModel
{
    protected $table = 'automotive_warranties';

    protected $casts = ['warranty_start' => 'date', 'warranty_end' => 'date'];

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function part() { return $this->belongsTo(Part::class); }
}
