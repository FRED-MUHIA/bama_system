<?php

namespace Modules\Agriculture\Models;

class WeatherRecord extends AgricultureModel
{
    protected $table = 'agriculture_weather_records';
    protected $casts = ['recorded_on' => 'date', 'rainfall_mm' => 'decimal:2', 'temperature_c' => 'decimal:2', 'humidity_percent' => 'decimal:2', 'wind_kph' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
}
