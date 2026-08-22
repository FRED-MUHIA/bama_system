<?php

namespace Shared\Communication\Models;

use App\Models\User;

class CommunicationNotification extends CommunicationModel
{
    protected $table = 'notifications';

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function notifiable() { return $this->morphTo(); }
}
