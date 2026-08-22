<?php

namespace Modules\Agriculture\Models;

use App\Models\User;

class VeterinaryRecord extends AgricultureModel
{
    protected $table = 'agriculture_veterinary_records';
    protected $casts = ['record_date' => 'date', 'next_due_date' => 'date', 'treatment_cost' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function animal() { return $this->belongsTo(Animal::class, 'animal_id'); }
    public function herd() { return $this->belongsTo(Herd::class, 'herd_id'); }
    public function veterinarian() { return $this->belongsTo(User::class, 'veterinarian_id'); }
}
