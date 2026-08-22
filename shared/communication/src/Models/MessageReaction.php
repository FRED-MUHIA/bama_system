<?php

namespace Shared\Communication\Models;

use App\Models\User;

class MessageReaction extends CommunicationModel
{
    public function message() { return $this->belongsTo(Message::class); }
    public function user() { return $this->belongsTo(User::class); }
}
