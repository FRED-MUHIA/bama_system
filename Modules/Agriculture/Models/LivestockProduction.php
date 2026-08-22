<?php

namespace Modules\Agriculture\Models;

class LivestockProduction extends AgricultureModel
{
    protected $table = 'agriculture_livestock_productions';
    protected $casts = ['production_date' => 'date', 'morning_quantity' => 'decimal:3', 'evening_quantity' => 'decimal:3', 'quantity' => 'decimal:3', 'damaged_quantity' => 'decimal:3', 'sold_quantity' => 'decimal:3', 'value' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function animal() { return $this->belongsTo(Animal::class, 'animal_id'); }
    public function herd() { return $this->belongsTo(Herd::class, 'herd_id'); }
}
