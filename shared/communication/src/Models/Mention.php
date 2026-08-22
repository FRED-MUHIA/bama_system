<?php

namespace Shared\Communication\Models;

class Mention extends CommunicationModel
{
    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function message() { return $this->belongsTo(Message::class); }
    public function announcement() { return $this->belongsTo(Announcement::class); }
}
