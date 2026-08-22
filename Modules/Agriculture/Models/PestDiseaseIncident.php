<?php

namespace Modules\Agriculture\Models;

class PestDiseaseIncident extends AgricultureModel
{
    protected $table = 'agriculture_pest_disease_incidents';
    protected $casts = ['observation_date' => 'date', 'follow_up_date' => 'date', 'photos' => 'array'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
    public function crop() { return $this->belongsTo(Crop::class, 'crop_id'); }
}
