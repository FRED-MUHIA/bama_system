<?php

namespace Modules\Agriculture\Models;

class Herd extends AgricultureModel
{
    protected $table = 'agriculture_herds';

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function animals() { return $this->hasMany(Animal::class, 'herd_id'); }
}
