<?php

namespace Modules\Agriculture\Models;

class Animal extends AgricultureModel
{
    protected $table = 'agriculture_animals';
    protected $casts = ['date_of_birth' => 'date', 'acquisition_date' => 'date', 'weight' => 'decimal:3'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function herd() { return $this->belongsTo(Herd::class, 'herd_id'); }
    public function mother() { return $this->belongsTo(self::class, 'mother_id'); }
    public function father() { return $this->belongsTo(self::class, 'father_id'); }
}
