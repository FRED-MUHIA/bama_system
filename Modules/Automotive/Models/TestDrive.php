<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\User;

class TestDrive extends AutomotiveModel
{
    protected $table = 'automotive_test_drives';

    protected $casts = ['test_date' => 'date', 'start_time' => 'datetime', 'return_time' => 'datetime'];

    public function client() { return $this->belongsTo(Client::class); }
    public function vehicleSale() { return $this->belongsTo(VehicleSale::class); }
    public function salesperson() { return $this->belongsTo(User::class, 'salesperson_id'); }
}
