<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class Comeback extends AutomotiveModel
{
    protected $table = 'automotive_comebacks';

    protected $casts = ['return_date' => 'date', 'warranty' => 'boolean'];

    public function originalJobCard() { return $this->belongsTo(JobCard::class, 'original_job_card_id'); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
}
