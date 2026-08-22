<?php

namespace Modules\PrintingBranding\Models;

class MaterialReservation extends PrintingBrandingModel
{
    protected $table = 'printing_material_reservations';

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
