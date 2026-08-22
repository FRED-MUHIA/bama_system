<?php

namespace Modules\Construction\Models;

use App\Models\Supplier;

class ConstructionContractor extends ConstructionModel
{
    protected $table = 'construction_contractors';

    protected $casts = ['financial_details' => 'array'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function subcontracts() { return $this->hasMany(ConstructionSubcontract::class, 'contractor_id'); }
}
