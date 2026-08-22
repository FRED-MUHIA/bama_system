<?php

namespace Modules\Agriculture\Models;

use App\Models\User;

class FarmWorker extends AgricultureModel
{
    protected $table = 'agriculture_farm_workers';
    protected $casts = ['duties' => 'array', 'work_schedule' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function field() { return $this->belongsTo(Field::class, 'field_id'); }
}
