<?php

namespace Modules\Agriculture\Models;

use App\Models\User;

class IrrigationSchedule extends AgricultureModel
{
    protected $table = 'agriculture_irrigation_schedules';
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'water_quantity' => 'decimal:3', 'cost' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function operator() { return $this->belongsTo(User::class, 'operator_id'); }
}
