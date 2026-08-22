<?php

namespace Modules\Agriculture\Models;

use App\Models\Client;

class Farmer extends AgricultureModel
{
    protected $table = 'agriculture_farmers';
    protected $casts = ['acreage' => 'decimal:4', 'crops' => 'array', 'input_advances' => 'decimal:2', 'deliveries_value' => 'decimal:2', 'payments_value' => 'decimal:2'];

    public function client() { return $this->belongsTo(Client::class); }
    public function contracts() { return $this->hasMany(FarmerContract::class, 'farmer_id'); }
}
