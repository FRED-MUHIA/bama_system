<?php

namespace Modules\RealEstate\Models;

class Commission extends RealEstateModel
{
    protected $table = 'real_estate_commissions';
    protected $casts = ['earned_date' => 'date', 'rate' => 'decimal:2', 'base_amount' => 'decimal:2', 'earned_amount' => 'decimal:2', 'paid_amount' => 'decimal:2'];

    public function agent() { return $this->belongsTo(Agent::class, 'real_estate_agent_id'); }
    public function commissionable() { return $this->morphTo(); }
}
