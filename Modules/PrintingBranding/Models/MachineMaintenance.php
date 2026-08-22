<?php

namespace Modules\PrintingBranding\Models;

class MachineMaintenance extends PrintingBrandingModel
{
    protected $table = 'printing_machine_maintenance';

    protected $casts = [
        'service_date' => 'date',
        'next_service_date' => 'date',
        'parts_used' => 'array',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }
}
