<?php

namespace Modules\Agriculture\Models;

class BreedingEvent extends AgricultureModel
{
    protected $table = 'agriculture_breeding_events';
    protected $casts = ['event_date' => 'date', 'pregnancy_check_date' => 'date', 'expected_birth_date' => 'date', 'birth_date' => 'date'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function animal() { return $this->belongsTo(Animal::class, 'animal_id'); }
    public function herd() { return $this->belongsTo(Herd::class, 'herd_id'); }
}
