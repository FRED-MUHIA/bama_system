<?php

namespace Modules\Construction\Models;

class ConstructionTenderChecklistItem extends ConstructionModel
{
    protected $table = 'construction_tender_checklist_items';

    protected $casts = ['required' => 'boolean', 'uploaded' => 'boolean', 'verified' => 'boolean', 'expiry_date' => 'date'];

    public function tender() { return $this->belongsTo(ConstructionTender::class, 'tender_id'); }
}
