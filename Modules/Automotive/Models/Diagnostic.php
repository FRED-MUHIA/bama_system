<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class Diagnostic extends AutomotiveModel
{
    protected $table = 'automotive_diagnostics';

    protected $casts = ['fault_codes' => 'array', 'attachments' => 'array', 'diagnostic_date' => 'date'];

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
}
