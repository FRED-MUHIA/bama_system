<?php

namespace Modules\Automotive\Models;

use App\Models\User;

class RoadTest extends AutomotiveModel
{
    protected $table = 'automotive_road_tests';

    protected $casts = ['observations' => 'array'];

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function tester() { return $this->belongsTo(User::class, 'tester_id'); }
}
