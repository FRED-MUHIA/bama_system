<?php

namespace Modules\Automotive\Models;

class InspectionItem extends AutomotiveModel
{
    protected $table = 'automotive_inspection_items';

    protected $casts = ['photos' => 'array'];

    public function inspection() { return $this->belongsTo(Inspection::class); }
}
