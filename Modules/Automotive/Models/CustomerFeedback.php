<?php

namespace Modules\Automotive\Models;

use App\Models\Client;

class CustomerFeedback extends AutomotiveModel
{
    protected $table = 'automotive_customer_feedback';

    protected $casts = ['scores' => 'array'];

    public function client() { return $this->belongsTo(Client::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function jobCard() { return $this->belongsTo(JobCard::class); }
}
