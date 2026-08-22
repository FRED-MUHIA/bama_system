<?php

namespace Modules\RealEstate\Models;

use App\Models\Invoice;

class Sale extends RealEstateModel
{
    protected $table = 'real_estate_sales';
    protected $casts = ['completion_date' => 'date', 'sale_price' => 'decimal:2', 'deposit' => 'decimal:2', 'balance' => 'decimal:2'];

    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'real_estate_unit_id'); }
    public function buyer() { return $this->belongsTo(Buyer::class, 'real_estate_buyer_id'); }
    public function agent() { return $this->belongsTo(Agent::class, 'real_estate_agent_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function commissions() { return $this->morphMany(Commission::class, 'commissionable'); }
}
