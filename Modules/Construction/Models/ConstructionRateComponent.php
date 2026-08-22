<?php

namespace Modules\Construction\Models;

class ConstructionRateComponent extends ConstructionModel
{
    protected $table = 'construction_rate_components';

    public function boqItem() { return $this->belongsTo(ConstructionBoqItem::class, 'boq_item_id'); }
}
