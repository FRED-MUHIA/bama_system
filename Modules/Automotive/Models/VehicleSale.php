<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\User;

class VehicleSale extends AutomotiveModel
{
    protected $table = 'automotive_vehicle_sales';

    protected $casts = ['meta' => 'array'];

    public function client() { return $this->belongsTo(Client::class); }
    public function salesperson() { return $this->belongsTo(User::class, 'salesperson_id'); }
}
