<?php

namespace Shared\Communication\Models;

use App\Models\User;

class AnnouncementAcknowledgement extends CommunicationModel
{
    protected $casts = [
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function announcement() { return $this->belongsTo(Announcement::class); }
    public function user() { return $this->belongsTo(User::class); }
}
