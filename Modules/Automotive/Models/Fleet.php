<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\User;

class Fleet extends AutomotiveModel
{
    protected $table = 'automotive_fleets';

    protected $casts = ['service_rules' => 'array'];

    public function client() { return $this->belongsTo(Client::class); }
    public function fleetManager() { return $this->belongsTo(User::class, 'fleet_manager_id'); }
    public function vehicles() { return $this->hasMany(Vehicle::class); }
}
