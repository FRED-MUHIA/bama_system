<?php

namespace Shared\Communication\Models;

use App\Models\User;

class ConversationPin extends CommunicationModel
{
    public function channel() { return $this->belongsTo(CommunicationChannel::class, 'communication_channel_id'); }
    public function message() { return $this->belongsTo(Message::class); }
    public function pinnedBy() { return $this->belongsTo(User::class, 'pinned_by'); }
}
