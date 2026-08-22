<?php

namespace Modules\Automotive\Models;

class PartCompatibility extends AutomotiveModel
{
    protected $table = 'automotive_part_compatibilities';

    public function part() { return $this->belongsTo(Part::class); }
}
