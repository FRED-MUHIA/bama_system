<?php

namespace Modules\Agriculture\Models;

class FarmSeason extends AgricultureModel
{
    protected $table = 'agriculture_farm_seasons';
    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
}
