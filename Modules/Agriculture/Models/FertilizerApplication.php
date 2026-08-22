<?php

namespace Modules\Agriculture\Models;

class FertilizerApplication extends AgricultureModel
{
    protected $table = 'agriculture_fertilizer_applications';
    protected $casts = ['application_date' => 'date', 'application_rate' => 'decimal:3', 'quantity' => 'decimal:3', 'cost' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function crop() { return $this->belongsTo(Crop::class, 'crop_id'); }
}
