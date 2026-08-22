<?php

namespace Modules\PrintingBranding\Models;

class Machine extends PrintingBrandingModel
{
    protected $table = 'printing_machines';

    public function maintenance()
    {
        return $this->hasMany(MachineMaintenance::class, 'machine_id');
    }
}
