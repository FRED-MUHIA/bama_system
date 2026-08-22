<?php

namespace Modules\Agriculture\Models;

use App\Models\User;

class EquipmentMaintenance extends AgricultureModel
{
    protected $table = 'agriculture_equipment_maintenance';
    protected $casts = ['service_date' => 'date', 'next_service_date' => 'date', 'parts_used' => 'array', 'cost' => 'decimal:2', 'meter_hours_reading' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function equipment() { return $this->belongsTo(Equipment::class, 'equipment_id'); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
}
