<?php

namespace Shared\Communication\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Shared\Communication\Models\Message;

class MessagePosted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->message->tenant_id.'.communication.channel.'.$this->message->communication_channel_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'communication.message.posted';
    }
}
