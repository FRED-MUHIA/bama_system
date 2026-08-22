<?php

namespace Modules\Automotive\Models;

use App\Models\Client;
use App\Models\Quotation;

class Estimate extends AutomotiveModel
{
    protected $table = 'automotive_estimates';

    protected $casts = ['sent_at' => 'datetime', 'approved_at' => 'datetime'];

    public function jobCard() { return $this->belongsTo(JobCard::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function items() { return $this->hasMany(EstimateItem::class); }
}
