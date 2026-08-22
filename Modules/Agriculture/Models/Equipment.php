<?php

namespace Modules\Agriculture\Models;

use App\Models\FixedAsset;
use App\Models\User;

class Equipment extends AgricultureModel
{
    protected $table = 'agriculture_equipment';
    protected $casts = ['purchase_date' => 'date', 'purchase_cost' => 'decimal:2', 'current_value' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function operator() { return $this->belongsTo(User::class, 'assigned_operator_id'); }
    public function fixedAsset() { return $this->belongsTo(FixedAsset::class); }
    public function maintenance() { return $this->hasMany(EquipmentMaintenance::class, 'equipment_id'); }
}
