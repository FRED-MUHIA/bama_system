<?php

namespace Shared\Communication\Models;

use App\Models\User;

class NotificationPreference extends CommunicationModel
{
    protected $casts = [
        'in_app' => 'boolean',
        'push' => 'boolean',
        'email' => 'boolean',
        'sms' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
