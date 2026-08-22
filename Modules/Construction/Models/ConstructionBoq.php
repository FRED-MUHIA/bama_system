<?php

namespace Modules\Construction\Models;

use App\Models\Client;
use App\Models\Project;

class ConstructionBoq extends ConstructionModel
{
    protected $table = 'construction_boqs';

    protected $casts = ['meta' => 'array'];

    public function project() { return $this->belongsTo(Project::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function sections() { return $this->hasMany(ConstructionBoqSection::class, 'boq_id'); }
    public function items() { return $this->hasMany(ConstructionBoqItem::class, 'boq_id'); }
}
