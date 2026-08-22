<?php

namespace Shared\Communication\Events;

use App\Models\User;
use App\Support\ActiveTenant;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresenceStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user, public string $status)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.ActiveTenant::id().'.communication.presence'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'communication.presence.updated';
    }

    public function broadcastWith(): array
    {
        return ['user_id' => $this->user->id, 'name' => $this->user->name, 'status' => $this->status];
    }
}
