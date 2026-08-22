<?php

namespace Shared\Communication\Models;

use App\Models\User;

class MessageRead extends CommunicationModel
{
    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function channel() { return $this->belongsTo(CommunicationChannel::class, 'communication_channel_id'); }
    public function message() { return $this->belongsTo(Message::class); }
    public function user() { return $this->belongsTo(User::class); }
}
