<?php

namespace Modules\Construction\Models;

use App\Models\Product;

class ConstructionBoqItem extends ConstructionModel
{
    protected $table = 'construction_boq_items';

    public function boq() { return $this->belongsTo(ConstructionBoq::class, 'boq_id'); }
    public function section() { return $this->belongsTo(ConstructionBoqSection::class, 'section_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function rateComponents() { return $this->hasMany(ConstructionRateComponent::class, 'boq_item_id'); }
}
