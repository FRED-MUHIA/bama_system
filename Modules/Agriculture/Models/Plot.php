<?php

namespace Modules\Agriculture\Models;

class Plot extends AgricultureModel
{
    protected $table = 'agriculture_plots';
    protected $casts = ['size' => 'decimal:4'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
}
