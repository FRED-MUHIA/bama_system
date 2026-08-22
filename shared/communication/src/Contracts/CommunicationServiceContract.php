<?php

namespace Shared\Communication\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Shared\Communication\Models\Announcement;
use Shared\Communication\Models\CommunicationChannel;
use Shared\Communication\Models\CommunicationNotification;
use Shared\Communication\Models\CommunicationSetting;
use Shared\Communication\Models\ConversationPin;
use Shared\Communication\Models\Message;
use Shared\Communication\Models\MessageAttachment;
use Shared\Communication\Models\MessageReaction;
use Shared\Communication\Models\SavedMessage;

interface CommunicationServiceContract
{
    public function createChannel(array $data, ?User $actor = null): CommunicationChannel;

    public function directChannel(User $sender, User $recipient): CommunicationChannel;

    public function sendMessage(CommunicationChannel $channel, User $sender, array $data): Message;

    public function publishAnnouncement(array $data, User $author): Announcement;

    public function notify(User $user, array $data, ?Model $notifiable = null): CommunicationNotification;

    public function markRead(CommunicationChannel $channel, User $user, ?Message $message = null): void;

    public function react(Message $message, User $user, string $reaction): MessageReaction;

    public function saveMessage(Message $message, User $user): SavedMessage;

    public function unsaveMessage(Message $message, User $user): void;

    public function pinMessage(Message $message, User $user, ?string $note = null): ConversationPin;

    public function unpinMessage(Message $message, User $user): void;

    public function editMessage(Message $message, User $user, string $body): Message;

    public function deleteMessage(Message $message, User $user, bool $forEveryone = false, ?string $reason = null): void;

    public function acknowledgeAnnouncement(Announcement $announcement, User $user, bool $acknowledge = false): void;

    public function settings(): CommunicationSetting;

    public function updateSettings(array $data, User $user): CommunicationSetting;

    public function employeeDirectory(User $user, array $filters = []): Collection;

    public function contextChannel(Model $record, User $user, ?string $name = null): CommunicationChannel;

    public function attachmentForDownload(MessageAttachment $attachment, User $user): MessageAttachment;

    public function accessibleChannels(User $user): Collection;

    public function accessibleAnnouncements(User $user, int $limit = 50): Collection;

    public function search(User $user, string $query): array;

    public function postErpEvent(string $eventType, string $body, array $context = []): Message;

    public function ensureRoleChannels(): Collection;

    public function metrics(User $user): array;
}
