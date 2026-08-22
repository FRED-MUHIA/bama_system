<?php

namespace Shared\Communication\Models;

use App\Models\User;

class Announcement extends CommunicationModel
{
    public const PRIORITIES = ['Low', 'Medium', 'High', 'Critical'];

    protected $casts = [
        'publish_at' => 'datetime',
        'expires_at' => 'datetime',
        'requires_acknowledgement' => 'boolean',
        'read_by' => 'array',
        'acknowledged_by' => 'array',
    ];

    public function channel() { return $this->belongsTo(CommunicationChannel::class, 'communication_channel_id'); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function mentions() { return $this->hasMany(Mention::class); }
    public function acknowledgements() { return $this->hasMany(AnnouncementAcknowledgement::class); }
}
