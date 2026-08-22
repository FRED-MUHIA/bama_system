<?php

namespace Shared\Communication\Models;

use App\Models\User;

class CommunicationAuditLog extends CommunicationModel
{
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function auditable() { return $this->morphTo(); }
}
