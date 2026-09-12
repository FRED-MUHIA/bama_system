<?php

namespace Modules\Chama\Models;

class ChamaAuditEvent extends ChamaModel
{
    protected $table = 'chama_audit_events';

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function auditable() { return $this->morphTo(); }
}
