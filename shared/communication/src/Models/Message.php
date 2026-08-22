<?php

namespace Shared\Communication\Models;

use App\Models\User;

class Message extends CommunicationModel
{
    public const STATUSES = ['Sent', 'Delivered', 'Read'];

    protected $casts = [
        'delivered_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function channel() { return $this->belongsTo(CommunicationChannel::class, 'communication_channel_id'); }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function parent() { return $this->belongsTo(Message::class, 'parent_id'); }
    public function related() { return $this->morphTo(); }
    public function reactions() { return $this->hasMany(MessageReaction::class); }
    public function attachments() { return $this->hasMany(MessageAttachment::class); }
    public function mentions() { return $this->hasMany(Mention::class); }
    public function reads() { return $this->hasMany(MessageRead::class); }
    public function savedBy() { return $this->hasMany(SavedMessage::class); }
    public function deletions() { return $this->hasMany(MessageDeletion::class); }
    public function replies() { return $this->hasMany(Message::class, 'parent_id'); }
}
