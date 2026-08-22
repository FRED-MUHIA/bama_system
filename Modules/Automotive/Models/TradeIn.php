<?php

namespace Modules\Automotive\Models;

use App\Models\Client;

class TradeIn extends AutomotiveModel
{
    protected $table = 'automotive_trade_ins';

    public function client() { return $this->belongsTo(Client::class); }
    public function vehicleSale() { return $this->belongsTo(VehicleSale::class); }
}
