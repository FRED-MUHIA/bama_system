<?php

namespace Shared\Communication\Models;

use App\Models\User;

class MessageDeletion extends CommunicationModel
{
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function message() { return $this->belongsTo(Message::class); }
    public function user() { return $this->belongsTo(User::class); }
}
