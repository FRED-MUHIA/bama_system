<?php

namespace Modules\RealEstate\Models;

use App\Models\Branch;
use App\Models\User;

class Agent extends RealEstateModel
{
    protected $table = 'real_estate_agents';

    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function listings() { return $this->hasMany(Listing::class, 'real_estate_agent_id'); }
    public function sales() { return $this->hasMany(Sale::class, 'real_estate_agent_id'); }
    public function commissions() { return $this->hasMany(Commission::class, 'real_estate_agent_id'); }
}
