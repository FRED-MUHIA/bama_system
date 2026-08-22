<?php

namespace Modules\Construction\Models;

class ConstructionBoqSection extends ConstructionModel
{
    protected $table = 'construction_boq_sections';

    public function boq() { return $this->belongsTo(ConstructionBoq::class, 'boq_id'); }
    public function items() { return $this->hasMany(ConstructionBoqItem::class, 'section_id'); }
}
