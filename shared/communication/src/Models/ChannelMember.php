<?php

namespace Shared\Communication\Models;

use App\Models\User;

class ChannelMember extends CommunicationModel
{
    protected $casts = [
        'muted' => 'boolean',
        'last_read_at' => 'datetime',
    ];

    public function channel() { return $this->belongsTo(CommunicationChannel::class, 'communication_channel_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function lastReadMessage() { return $this->belongsTo(Message::class, 'last_read_message_id'); }
}
