<?php

namespace Modules\PrintingBranding\Models;

class PrintMethod extends PrintingBrandingModel
{
    protected $table = 'printing_print_methods';

    protected $casts = [
        'costing_rules' => 'array',
        'is_active' => 'boolean',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }
}
