<?php

namespace Modules\PrintingBranding\Models;

class PrintingAlert extends PrintingBrandingModel
{
    protected $table = 'printing_alerts';

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
