<?php

namespace Modules\Automotive\Models;

use App\Models\Invoice;
use App\Models\User;

class VehicleRelease extends AutomotiveModel
{
    protected $table = 'automotive_vehicle_releases';

    protected $casts = ['released_at' => 'datetime'];

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function releasedBy() { return $this->belongsTo(User::class, 'released_by'); }
}
