<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class Inspection extends AutomotiveModel
{
    protected $table = 'automotive_inspections';

    protected $casts = ['inspection_date' => 'date', 'photos' => 'array'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function checkIn() { return $this->belongsTo(CheckIn::class, 'check_in_id'); }
    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function items() { return $this->hasMany(InspectionItem::class); }
}
