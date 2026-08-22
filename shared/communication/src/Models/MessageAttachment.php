<?php

namespace Shared\Communication\Models;

use App\Models\User;

class MessageAttachment extends CommunicationModel
{
    protected $casts = [
        'metadata' => 'array',
    ];

    public function message() { return $this->belongsTo(Message::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
