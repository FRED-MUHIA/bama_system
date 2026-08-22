<?php

namespace Shared\Communication\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Shared\Communication\Models\CommunicationChannel;

class TypingIndicatorUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public CommunicationChannel $channel, public User $user, public bool $typing)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->channel->tenant_id.'.communication.channel.'.$this->channel->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'communication.typing.updated';
    }

    public function broadcastWith(): array
    {
        return ['user_id' => $this->user->id, 'name' => $this->user->name, 'typing' => $this->typing];
    }
}
