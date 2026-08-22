<?php

namespace Shared\Communication\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\IamRole;
use App\Models\Project;
use App\Models\User;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Shared\Communication\Contracts\CommunicationServiceContract;
use Shared\Communication\Events\MessagePosted;
use Shared\Communication\Models\Announcement;
use Shared\Communication\Models\AnnouncementAcknowledgement;
use Shared\Communication\Models\ChannelMember;
use Shared\Communication\Models\CommunicationAuditLog;
use Shared\Communication\Models\CommunicationChannel;
use Shared\Communication\Models\CommunicationNotification;
use Shared\Communication\Models\CommunicationPermission;
use Shared\Communication\Models\CommunicationSetting;
use Shared\Communication\Models\ConversationPin;
use Shared\Communication\Models\Mention;
use Shared\Communication\Models\Message;
use Shared\Communication\Models\MessageAttachment;
use Shared\Communication\Models\MessageDeletion;
use Shared\Communication\Models\MessageReaction;
use Shared\Communication\Models\MessageRead;
use Shared\Communication\Models\SavedMessage;

class CommunicationService implements CommunicationServiceContract
{
    public function createChannel(array $data, ?User $actor = null): CommunicationChannel
    {
        $actor ??= auth()->user();
        $this->assertChatEnabled();
        $this->assertBusinessUser($actor);
        $this->assertCanCreateChannel($actor, $data['type'] ?? 'Group');
        $name = $data['name'] ?? 'Untitled Channel';
        $this->assertScopedReferences($data);

        return DB::transaction(function () use ($data, $actor, $name) {
            $channel = CommunicationChannel::create([
                'owner_id' => $actor?->id,
                'department_id' => $data['department_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'team_id' => $data['team_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'industry' => $data['industry'] ?? null,
                'role_slug' => $data['role_slug'] ?? null,
                'record_type' => $data['record_type'] ?? null,
                'record_id' => $data['record_id'] ?? null,
                'name' => $name,
                'description' => $data['description'] ?? null,
                'avatar_path' => $data['avatar_path'] ?? null,
                'slug' => $data['slug'] ?? $this->uniqueSlug($name),
                'type' => $data['type'] ?? 'Group',
                'visibility' => $data['visibility'] ?? 'Private',
                'is_private' => (bool) ($data['is_private'] ?? true),
                'settings' => $data['settings'] ?? null,
            ]);

            $memberIds = collect($data['member_ids'] ?? [])
                ->push($actor?->id)
                ->filter()
                ->unique()
                ->values();

            $memberIds->each(fn ($userId) => $this->addMember($channel, (int) $userId, $userId === $actor?->id ? 'Admin' : 'Member'));
            $this->audit('channel.created', $channel);

            return $channel->load('members.user');
        });
    }

    public function directChannel(User $sender, User $recipient): CommunicationChannel
    {
        $this->assertChatEnabled();
        $this->assertCanDirectMessage($sender, $recipient);
        $ids = collect([$sender->id, $recipient->id])->sort()->values();
        $slug = 'direct-'.$ids->implode('-');

        $channel = CommunicationChannel::where('slug', $slug)->first();
        if ($channel) {
            return $channel;
        }

        return $this->createChannel([
            'name' => $sender->name.' / '.$recipient->name,
            'slug' => $slug,
            'type' => 'Direct',
            'visibility' => 'Private',
            'member_ids' => $ids->all(),
        ], $sender);
    }

    public function sendMessage(CommunicationChannel $channel, User $sender, array $data): Message
    {
        $this->assertChatEnabled();
        if (! $sender->hasPermission('communication.send') && ! $sender->hasPermission('communication.admin')) {
            throw ValidationException::withMessages(['body' => 'You do not have permission to send messages.']);
        }
        $this->assertChannelAccess($channel, $sender);
        $this->assertParentMessage($channel, $data['parent_id'] ?? null);
        $this->assertMentionPermissions($sender, $data['body']);
        $this->assertAttachmentsAllowed($data['attachments'] ?? []);

        return DB::transaction(function () use ($channel, $sender, $data) {
            $message = Message::create([
                'communication_channel_id' => $channel->id,
                'sender_id' => $sender->id,
                'parent_id' => $data['parent_id'] ?? null,
                'related_type' => $data['related_type'] ?? null,
                'related_id' => $data['related_id'] ?? null,
                'message_type' => $data['message_type'] ?? 'Message',
                'body' => $this->sanitizeBody($data['body']),
                'status' => 'Delivered',
                'delivered_at' => now(),
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($data['attachments'] ?? [] as $attachment) {
                $message->attachments()->create([
                    'uploaded_by' => $sender->id,
                    'document_media_id' => $attachment['document_media_id'] ?? null,
                    'disk' => $attachment['disk'] ?? 'local',
                    'file_name' => $attachment['file_name'],
                    'mime_type' => $attachment['mime_type'] ?? null,
                    'file_size' => $attachment['file_size'] ?? 0,
                    'path' => $attachment['path'] ?? null,
                    'is_voice_note' => (bool) ($attachment['is_voice_note'] ?? false),
                    'metadata' => $attachment['metadata'] ?? null,
                ]);
                $this->audit('file.shared', $message);
            }

            $channel->update(['last_message_at' => now()]);
            $mentions = $this->recordMentions($message, $data['body'], $sender);
            $this->notifyChannelMembers($message, $sender, $mentions);
            $this->audit('message.sent', $message);

            event(new MessagePosted($message->load('sender', 'attachments')));

            return $message->load('sender', 'attachments', 'mentions');
        });
    }

    public function publishAnnouncement(array $data, User $author): Announcement
    {
        $this->assertChatEnabled();
        if (! $author->hasPermission('communication.announcements.create') && ! $author->hasPermission('communication.announce') && ! $author->hasPermission('communication.admin')) {
            throw ValidationException::withMessages(['announcement' => 'You do not have permission to publish announcements.']);
        }
        $this->assertScopedReferences($data);
        $this->assertMentionPermissions($author, $data['body']);

        return DB::transaction(function () use ($data, $author) {
            $announcement = Announcement::create([
                'communication_channel_id' => $data['communication_channel_id'] ?? null,
                'author_id' => $author->id,
                'scope_type' => $data['scope_type'] ?? 'Company',
                'scope_id' => $data['scope_id'] ?? null,
                'industry' => $data['industry'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'title' => $data['title'],
                'body' => $this->sanitizeBody($data['body']),
                'priority' => $data['priority'] ?? 'Medium',
                'publish_at' => $data['publish_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? null,
                'requires_acknowledgement' => (bool) ($data['requires_acknowledgement'] ?? false),
                'status' => $data['status'] ?? 'Published',
            ]);

            $this->recordAnnouncementMentions($announcement);
            $this->notifyAnnouncementAudience($announcement);
            $this->audit('announcement.published', $announcement);

            return $announcement;
        });
    }

    public function notify(User $user, array $data, ?Model $notifiable = null): CommunicationNotification
    {
        $this->assertBusinessUser($user);

        return CommunicationNotification::create([
            'user_id' => $user->id,
            'notifiable_type' => $notifiable?->getMorphClass(),
            'notifiable_id' => $notifiable?->getKey(),
            'notification_type' => $data['notification_type'] ?? 'Message',
            'delivery_channel' => $data['delivery_channel'] ?? 'In-App',
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'status' => $data['status'] ?? 'Unread',
            'action_url' => $data['action_url'] ?? null,
            'payload' => $data['payload'] ?? null,
            'delivered_at' => now(),
        ]);
    }

    public function markRead(CommunicationChannel $channel, User $user, ?Message $message = null): void
    {
        $this->assertChannelAccess($channel, $user);
        $message ??= $channel->messages()->latest()->first();

        DB::transaction(function () use ($channel, $user, $message) {
            ChannelMember::where('communication_channel_id', $channel->id)
                ->where('user_id', $user->id)
                ->update([
                    'last_read_message_id' => $message?->id,
                    'last_read_at' => now(),
                ]);

            if ($message && Schema::hasTable('message_reads')) {
                MessageRead::updateOrCreate(
                    ['message_id' => $message->id, 'user_id' => $user->id],
                    [
                        'communication_channel_id' => $channel->id,
                        'read_at' => now(),
                    ]
                );
            }

            CommunicationNotification::where('user_id', $user->id)
                ->where('status', 'Unread')
                ->where('payload->channel_id', $channel->id)
                ->update(['status' => 'Read', 'read_at' => now()]);
        });

        if ($message) {
            $message->update(['status' => 'Read']);
        }
    }

    public function react(Message $message, User $user, string $reaction): MessageReaction
    {
        $this->assertChannelAccess($message->channel, $user);
        $reaction = trim(strip_tags($reaction));

        if ($reaction === '' || mb_strlen($reaction) > 80) {
            throw ValidationException::withMessages(['reaction' => 'Select a valid reaction.']);
        }

        $reactionModel = MessageReaction::firstOrCreate([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => $reaction,
        ]);
        $this->audit('message.reaction.added', $message);

        return $reactionModel;
    }

    public function saveMessage(Message $message, User $user): SavedMessage
    {
        $this->assertChannelAccess($message->channel, $user);

        return SavedMessage::firstOrCreate([
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);
    }

    public function unsaveMessage(Message $message, User $user): void
    {
        $this->assertChannelAccess($message->channel, $user);

        SavedMessage::where('message_id', $message->id)->where('user_id', $user->id)->delete();
    }

    public function pinMessage(Message $message, User $user, ?string $note = null): ConversationPin
    {
        $this->assertChannelAccess($message->channel, $user);
        if (! $user->hasPermission('communication.manage_channel') && ! $user->hasPermission('communication.admin')) {
            throw ValidationException::withMessages(['message' => 'You do not have permission to pin messages.']);
        }

        $pin = ConversationPin::updateOrCreate(
            ['communication_channel_id' => $message->communication_channel_id, 'message_id' => $message->id],
            ['pinned_by' => $user->id, 'note' => $note]
        );
        $this->audit('message.pinned', $message);

        return $pin;
    }

    public function unpinMessage(Message $message, User $user): void
    {
        $this->assertChannelAccess($message->channel, $user);
        if (! $user->hasPermission('communication.manage_channel') && ! $user->hasPermission('communication.admin')) {
            throw ValidationException::withMessages(['message' => 'You do not have permission to unpin messages.']);
        }

        ConversationPin::where('message_id', $message->id)->delete();
        $this->audit('message.unpinned', $message);
    }

    public function editMessage(Message $message, User $user, string $body): Message
    {
        $this->assertChannelAccess($message->channel, $user);
        $settings = $this->settings();

        if (! $settings->allow_message_editing) {
            throw ValidationException::withMessages(['body' => 'Message editing is disabled.']);
        }

        if ((int) $message->sender_id !== (int) $user->id && ! $user->hasPermission('communication.moderate')) {
            throw ValidationException::withMessages(['body' => 'You can only edit your own messages.']);
        }

        if ((int) $message->sender_id === (int) $user->id && $settings->message_edit_time_limit_minutes && $message->created_at->lt(now()->subMinutes($settings->message_edit_time_limit_minutes))) {
            throw ValidationException::withMessages(['body' => 'The edit window for this message has expired.']);
        }

        $old = $message->getAttributes();
        $message->update([
            'body' => $this->sanitizeBody($body),
            'edited_at' => now(),
            'edited_by' => $user->id,
        ]);
        $this->audit('message.edited', $message, $old);

        return $message->refresh();
    }

    public function deleteMessage(Message $message, User $user, bool $forEveryone = false, ?string $reason = null): void
    {
        $this->assertChannelAccess($message->channel, $user);
        if (! $this->settings()->allow_message_deletion) {
            throw ValidationException::withMessages(['message' => 'Message deletion is disabled.']);
        }

        if ($forEveryone) {
            if ((int) $message->sender_id !== (int) $user->id && ! $user->hasPermission('communication.moderate')) {
                throw ValidationException::withMessages(['message' => 'You cannot delete this message for everyone.']);
            }

            $message->update([
                'deleted_at' => now(),
                'deleted_by' => $user->id,
                'moderation_reason' => $reason,
                'body' => '[message deleted]',
            ]);
            $this->audit('message.deleted_for_everyone', $message);

            return;
        }

        MessageDeletion::updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            ['deleted_at' => now()]
        );
        $this->audit('message.deleted_for_user', $message);
    }

    public function acknowledgeAnnouncement(Announcement $announcement, User $user, bool $acknowledge = false): void
    {
        $this->assertAnnouncementAccess($announcement, $user);

        AnnouncementAcknowledgement::updateOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $user->id],
            [
                'read_at' => now(),
                'acknowledged_at' => $acknowledge ? now() : null,
            ]
        );
    }

    public function settings(): CommunicationSetting
    {
        return CommunicationSetting::firstOrCreate(
            ['business_id' => ActiveBusiness::id()],
            $this->defaultSettings()
        );
    }

    public function updateSettings(array $data, User $user): CommunicationSetting
    {
        if (! $user->hasPermission('communication.settings') && ! $user->hasPermission('communication.admin')) {
            throw ValidationException::withMessages(['settings' => 'You do not have permission to update communication settings.']);
        }

        $settings = $this->settings();
        $old = $settings->getAttributes();
        $settings->update($data);
        $this->audit('communication.settings.updated', $settings, $old);

        return $settings->refresh();
    }

    public function employeeDirectory(User $user, array $filters = []): Collection
    {
        $this->assertBusinessUser($user);
        $memberships = DB::table('business_user')
            ->leftJoin('iam_roles', 'iam_roles.id', '=', 'business_user.iam_role_id')
            ->leftJoin('departments', 'departments.id', '=', 'business_user.department_id')
            ->leftJoin('branches', 'branches.id', '=', 'business_user.branch_id')
            ->where('business_user.business_id', ActiveBusiness::id())
            ->where('business_user.status', 'Active')
            ->select([
                'business_user.user_id',
                'business_user.department_id',
                'business_user.branch_id',
                'iam_roles.slug as role_slug',
                'iam_roles.name as role_name',
                'departments.name as department_name',
                'branches.name as branch_name',
            ])
            ->get()
            ->keyBy('user_id');

        $query = User::query()->whereIn('id', $memberships->keys());

        if ($search = $filters['q'] ?? null) {
            $like = '%'.$search.'%';
            $matchingMembershipUserIds = $memberships
                ->filter(fn ($row) => Str::contains(Str::lower(($row->department_name ?? '').' '.($row->branch_name ?? '').' '.($row->role_name ?? '').' '.($row->role_slug ?? '')), Str::lower($search)))
                ->keys();

            $query->where(function ($users) use ($like, $matchingMembershipUserIds) {
                $users->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhereIn('id', $matchingMembershipUserIds);
            });
        }

        return $query->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'username', 'email', 'job_title', 'photo_path', 'role', 'presence_status', 'presence_custom_status', 'last_seen_at'])
            ->map(function (User $directoryUser) use ($memberships) {
                $membership = $memberships[$directoryUser->id] ?? null;

                return [
                    'id' => $directoryUser->id,
                    'name' => $directoryUser->name,
                    'username' => $directoryUser->username,
                    'email' => $directoryUser->email,
                    'job_title' => $directoryUser->job_title,
                    'photo_path' => $directoryUser->photo_path,
                    'role' => $membership?->role_name ?? Str::headline($directoryUser->role),
                    'role_slug' => $membership?->role_slug ?? $directoryUser->role,
                    'department' => $membership?->department_name,
                    'branch' => $membership?->branch_name,
                    'presence_status' => $directoryUser->presence_status ?? 'Offline',
                    'presence_custom_status' => $directoryUser->presence_custom_status,
                    'last_seen_at' => $directoryUser->last_seen_at,
                    'online' => in_array($directoryUser->presence_status, ['Online', 'Away'], true),
                ];
            });
    }

    public function contextChannel(Model $record, User $user, ?string $name = null): CommunicationChannel
    {
        $this->assertBusinessUser($user);
        $recordBusinessId = $record->business_id ?? ActiveBusiness::id();

        if ((int) $recordBusinessId !== (int) ActiveBusiness::id()) {
            throw ValidationException::withMessages(['record' => 'This record does not belong to the active business.']);
        }

        $type = $record->getMorphClass();
        $id = $record->getKey();

        $channel = CommunicationChannel::firstOrCreate(
            ['record_type' => $type, 'record_id' => $id, 'type' => 'Record'],
            [
                'owner_id' => $user->id,
                'name' => $name ?: class_basename($record).' Discussion #'.$id,
                'description' => 'Internal discussion attached to '.class_basename($record).' #'.$id.'.',
                'slug' => $this->uniqueSlug('record-'.Str::slug(class_basename($record)).'-'.$id),
                'visibility' => 'Private',
                'is_private' => true,
                'settings' => ['contextual' => true],
            ]
        );

        $this->addMember($channel, $user->id, 'Admin');

        return $channel->refresh();
    }

    public function attachmentForDownload(MessageAttachment $attachment, User $user): MessageAttachment
    {
        $message = $attachment->message()->with('channel')->firstOrFail();
        $this->assertChannelAccess($message->channel, $user);

        return $attachment;
    }

    public function accessibleChannels(User $user): Collection
    {
        $this->assertBusinessUser($user);

        $businessRole = $this->roleSlugFor($user);
        $membership = $this->businessMembership($user);
        $teamIds = $user->teams()->pluck('teams.id');

        return CommunicationChannel::query()
            ->when(Schema::hasColumn('communication_channels', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->where(function ($query) use ($user, $businessRole, $membership, $teamIds) {
                $query->whereHas('members', fn ($members) => $members->where('user_id', $user->id)->where('status', 'Active'))
                    ->orWhere('visibility', 'Public')
                    ->orWhere(fn ($scoped) => $scoped->where('type', 'Role')->where('role_slug', $businessRole))
                    ->orWhere(fn ($scoped) => $scoped->where('type', 'Department')->where('department_id', $membership?->department_id))
                    ->orWhere(fn ($scoped) => $scoped->where('type', 'Branch')->where('branch_id', $membership?->branch_id))
                    ->orWhere(fn ($scoped) => $scoped->where('type', 'Team')->whereIn('team_id', $teamIds))
                    ->orWhere(fn ($scoped) => $scoped->where('type', 'Industry')->where('industry', ActiveBusiness::current()?->industry));
            })
            ->latest('last_message_at')
            ->get();
    }

    public function accessibleAnnouncements(User $user, int $limit = 50): Collection
    {
        $this->assertBusinessUser($user);
        $membership = $this->businessMembership($user);
        $channelIds = $this->accessibleChannels($user)->pluck('id');
        $industry = ActiveBusiness::current()?->industry;

        return Announcement::with('author', 'acknowledgements')
            ->where('status', 'Published')
            ->where(function ($query) use ($membership, $channelIds, $industry) {
                $query->where('scope_type', 'Company')
                    ->when($industry, fn ($audience) => $audience->orWhere(fn ($scoped) => $scoped->where('scope_type', 'Industry')->where('industry', $industry)))
                    ->when($membership?->branch_id, fn ($audience) => $audience->orWhere(fn ($scoped) => $scoped->where('scope_type', 'Branch')->where('branch_id', $membership->branch_id)))
                    ->when($membership?->department_id, fn ($audience) => $audience->orWhere(fn ($scoped) => $scoped->where('scope_type', 'Department')->where('department_id', $membership->department_id)))
                    ->when($channelIds->isNotEmpty(), fn ($audience) => $audience->orWhereIn('communication_channel_id', $channelIds));
            })
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function search(User $user, string $query): array
    {
        $channelIds = $this->accessibleChannels($user)->pluck('id');
        $businessUserIds = $this->businessUserIds();

        return [
            'channels' => CommunicationChannel::whereIn('id', $channelIds)->where('name', 'like', '%'.$query.'%')->limit(25)->get(),
            'messages' => Message::with('sender')->whereIn('communication_channel_id', $channelIds)->where('body', 'like', '%'.$query.'%')->latest()->limit(50)->get(),
            'files' => \Shared\Communication\Models\MessageAttachment::whereHas('message', fn ($messages) => $messages->whereIn('communication_channel_id', $channelIds))->where('file_name', 'like', '%'.$query.'%')->limit(25)->get(),
            'users' => User::whereIn('id', $businessUserIds)
                ->where(fn ($users) => $users->where('name', 'like', '%'.$query.'%')->orWhere('email', 'like', '%'.$query.'%')->orWhere('username', 'like', '%'.$query.'%'))
                ->limit(25)
                ->get(['id', 'name', 'username', 'email', 'job_title', 'presence_status']),
            'departments' => Department::where('name', 'like', '%'.$query.'%')->limit(25)->get(),
            'branches' => Branch::where('name', 'like', '%'.$query.'%')->limit(25)->get(),
        ];
    }

    public function postErpEvent(string $eventType, string $body, array $context = []): Message
    {
        $industry = $context['industry'] ?? ActiveBusiness::current()?->industry ?? 'shared';
        $channel = CommunicationChannel::firstOrCreate(
            ['slug' => 'erp-events-'.$industry],
            [
                'name' => Str::headline($industry).' ERP Events',
                'type' => 'Industry',
                'visibility' => 'Public',
                'is_private' => false,
                'industry' => $industry,
                'settings' => ['system_channel' => true],
            ]
        );

        $sender = $context['sender'] ?? auth()->user() ?? User::where('role', 'super_admin')->first() ?? User::firstOrFail();

        return $this->sendMessage($channel, $sender, [
            'body' => $body,
            'message_type' => 'ERP Event',
            'related_type' => $context['related_type'] ?? null,
            'related_id' => $context['related_id'] ?? null,
            'metadata' => ['event_type' => $eventType] + ($context['metadata'] ?? []),
        ]);
    }

    public function ensureRoleChannels(): Collection
    {
        if (! Schema::hasTable('iam_roles')) {
            return collect();
        }

        return IamRole::where('business_id', ActiveBusiness::id())
            ->get()
            ->map(function (IamRole $role) {
                $channel = CommunicationChannel::firstOrCreate(
                    ['slug' => 'role-'.$role->slug],
                    [
                        'name' => Str::headline($role->slug).' Channel',
                        'type' => 'Role',
                        'visibility' => 'Private',
                        'role_slug' => $role->slug,
                        'settings' => ['auto_managed' => true],
                    ]
                );

                DB::table('business_user')
                    ->where('business_id', ActiveBusiness::id())
                    ->where('iam_role_id', $role->id)
                    ->pluck('user_id')
                    ->each(fn ($userId) => $this->addMember($channel, (int) $userId));

                return $channel->refresh();
            });
    }

    public function metrics(User $user): array
    {
        $channels = $this->accessibleChannels($user);
        $channelIds = $channels->pluck('id');

        return [
            'Unread Messages' => CommunicationNotification::where('user_id', $user->id)->where('status', 'Unread')->count(),
            'Recent Conversations' => $channels->whereNotNull('last_message_at')->count(),
            'Department Activity' => Message::whereIn('communication_channel_id', $channelIds)->where('message_type', 'Message')->whereDate('created_at', today())->count(),
            'Announcements' => Announcement::where('status', 'Published')->count(),
            'Pending Mentions' => Mention::where('mentioned_type', 'User')->where('mentioned_id', $user->id)->whereNull('notified_at')->count(),
            'Team Activity' => Message::whereIn('communication_channel_id', $channelIds)->whereDate('created_at', today())->count(),
        ];
    }

    private function addMember(CommunicationChannel $channel, int $userId, string $role = 'Member'): void
    {
        $this->assertBusinessUser(User::findOrFail($userId));

        ChannelMember::updateOrCreate(
            ['communication_channel_id' => $channel->id, 'user_id' => $userId],
            ['member_role' => $role, 'status' => 'Active']
        );
    }

    private function assertChatEnabled(): void
    {
        if (! $this->settings()->chat_enabled) {
            throw ValidationException::withMessages(['communication' => 'Internal communication is disabled for this business.']);
        }
    }

    private function assertCanCreateChannel(User $actor, string $type): void
    {
        if ($actor->hasPermission('communication.admin') || $actor->hasPermission('communication.manage_channel') || $actor->hasPermission('communication.manage')) {
            return;
        }

        if ($type === 'Group' && $actor->hasPermission('communication.create_group') && $this->settings()->allow_employee_group_creation) {
            return;
        }

        if ($actor->hasPermission('communication.create_channel')) {
            return;
        }

        throw ValidationException::withMessages(['channel' => 'You do not have permission to create this conversation.']);
    }

    private function assertBusinessUser(?User $user): void
    {
        if (! $user || ! Schema::hasTable('business_user')) {
            throw ValidationException::withMessages(['user' => 'A valid employee is required.']);
        }

        $exists = DB::table('business_user')
            ->where('business_id', ActiveBusiness::id())
            ->where('user_id', $user->id)
            ->where('status', 'Active')
            ->exists();

        if (! $exists && $user->role !== 'super_admin') {
            throw ValidationException::withMessages(['user' => 'This employee does not belong to the active business.']);
        }
    }

    private function assertScopedReferences(array $data): void
    {
        foreach ([
            'department_id' => Department::class,
            'branch_id' => Branch::class,
            'team_id' => \App\Models\Team::class,
            'project_id' => Project::class,
        ] as $key => $model) {
            if (! empty($data[$key]) && ! $model::whereKey($data[$key])->exists()) {
                throw ValidationException::withMessages([$key => 'The selected record is not available in the active business.']);
            }
        }

        if (! empty($data['communication_channel_id']) && ! CommunicationChannel::whereKey($data['communication_channel_id'])->exists()) {
            throw ValidationException::withMessages(['communication_channel_id' => 'The selected conversation is not available in the active business.']);
        }
    }

    private function assertParentMessage(CommunicationChannel $channel, ?int $parentId): void
    {
        if (! $parentId) {
            return;
        }

        if (! Message::whereKey($parentId)->where('communication_channel_id', $channel->id)->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'Replies must target a message in the same conversation.']);
        }
    }

    private function assertMentionPermissions(User $sender, string $body): void
    {
        if (! preg_match('/@(everyone|here)\b/i', $body)) {
            return;
        }

        if (! $this->settings()->allow_everyone_mentions && ! $sender->hasPermission('communication.mass_mention') && ! $sender->hasPermission('communication.admin')) {
            throw ValidationException::withMessages(['body' => 'You do not have permission to mention everyone.']);
        }
    }

    private function assertAttachmentsAllowed(array $attachments): void
    {
        if ($attachments === []) {
            return;
        }

        $settings = $this->settings();
        if (! $settings->allow_file_sharing) {
            throw ValidationException::withMessages(['attachments' => 'File sharing is disabled for this business.']);
        }

        $allowed = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'image/png',
            'image/jpeg',
            'image/webp',
            'audio/mpeg',
            'audio/mp4',
            'audio/wav',
            'audio/webm',
        ];

        foreach ($attachments as $index => $attachment) {
            if (($attachment['file_size'] ?? 0) > ($settings->max_attachment_size_kb * 1024)) {
                throw ValidationException::withMessages(["attachments.$index" => 'The attachment is larger than this business allows.']);
            }

            if (! empty($attachment['mime_type']) && ! in_array($attachment['mime_type'], $allowed, true)) {
                throw ValidationException::withMessages(["attachments.$index" => 'This attachment type is not allowed.']);
            }
        }
    }

    private function assertAnnouncementAccess(Announcement $announcement, User $user): void
    {
        $this->assertBusinessUser($user);

        if ((int) $announcement->business_id !== (int) ActiveBusiness::id()) {
            throw ValidationException::withMessages(['announcement' => 'This announcement does not belong to the active business.']);
        }

        if ($announcement->communication_channel_id) {
            $this->assertChannelAccess($announcement->channel, $user);
        }

        if (! $this->accessibleAnnouncements($user, 5000)->contains('id', $announcement->id)) {
            throw ValidationException::withMessages(['announcement' => 'You do not have access to this announcement.']);
        }
    }

    private function sanitizeBody(string $body): string
    {
        return trim(strip_tags($body));
    }

    private function businessUserIds(): Collection
    {
        if (! Schema::hasTable('business_user')) {
            return collect();
        }

        return DB::table('business_user')
            ->where('business_id', ActiveBusiness::id())
            ->where('status', 'Active')
            ->pluck('user_id');
    }

    private function defaultSettings(): array
    {
        return [
            'tenant_id' => ActiveTenant::id(),
            'chat_enabled' => true,
            'allow_direct_messages' => true,
            'allow_employee_group_creation' => false,
            'allow_file_sharing' => true,
            'max_attachment_size_kb' => 10240,
            'allow_message_editing' => true,
            'message_edit_time_limit_minutes' => 15,
            'allow_message_deletion' => true,
            'enable_read_receipts' => true,
            'enable_presence' => true,
            'enable_typing_indicators' => true,
            'allow_everyone_mentions' => false,
            'auto_department_channels' => true,
            'auto_team_channels' => true,
            'auto_branch_channels' => true,
            'message_retention_days' => null,
            'industry_channel_templates' => $this->industryChannelTemplates(ActiveBusiness::current()?->industry),
        ];
    }

    private function industryChannelTemplates(?string $industry): array
    {
        $industry = Str::of($industry ?: 'general')->snake(' ')->slug('-')->toString();

        return [
            'agriculture' => ['farm-operations', 'livestock', 'crop-planning', 'harvest', 'equipment', 'procurement'],
            'hospitality' => ['front-desk', 'reservations', 'housekeeping', 'restaurant', 'maintenance', 'events'],
            'construction' => ['projects', 'sites', 'procurement', 'safety', 'equipment'],
            'real-estate' => ['property-management', 'leasing', 'maintenance', 'collections'],
            'retail' => ['sales', 'inventory', 'cashiers', 'warehouse'],
            'healthcare' => ['administration', 'front-desk', 'billing', 'pharmacy', 'operations'],
            'education' => ['administration', 'teachers', 'finance', 'operations'],
        ][$industry] ?? ['general', 'operations', 'finance', 'announcements'];
    }

    private function assertChannelAccess(CommunicationChannel $channel, User $user): void
    {
        if ((int) $channel->business_id !== (int) ActiveBusiness::id()) {
            throw ValidationException::withMessages(['channel' => 'This conversation does not belong to the active business.']);
        }

        $this->assertBusinessUser($user);

        if ($user->hasPermission('communication.admin')) {
            return;
        }

        if ($this->accessibleChannels($user)->contains('id', $channel->id)) {
            return;
        }

        throw ValidationException::withMessages(['channel' => 'You do not have access to this communication channel.']);
    }

    private function assertCanDirectMessage(User $sender, User $recipient): void
    {
        $this->assertBusinessUser($sender);
        $this->assertBusinessUser($recipient);

        if (! $this->settings()->allow_direct_messages) {
            throw ValidationException::withMessages(['recipient_id' => 'Direct messaging is disabled for this business.']);
        }

        $senderRole = $this->roleSlugFor($sender);
        $recipientRole = $this->roleSlugFor($recipient);
        $rule = CommunicationPermission::where('role_slug', $senderRole)
            ->where('target_type', 'Role')
            ->where('target_role_slug', $recipientRole)
            ->first();

        if ($rule && ! $rule->can_message) {
            throw ValidationException::withMessages(['recipient_id' => 'Your role cannot message this recipient.']);
        }
    }

    private function notifyChannelMembers(Message $message, User $sender, Collection $mentions): void
    {
        $message->channel->members()
            ->where('user_id', '!=', $sender->id)
            ->with('user')
            ->get()
            ->each(function (ChannelMember $member) use ($message, $mentions) {
                $mentioned = $mentions->contains(fn (Mention $mention) => $mention->mentioned_type === 'User' && (int) $mention->mentioned_id === (int) $member->user_id);
                $this->notify($member->user, [
                    'notification_type' => $mentioned ? 'Mention' : 'Message',
                    'title' => $mentioned ? 'You were mentioned' : 'New message in '.$message->channel->name,
                    'body' => Str::limit($message->body, 160),
                    'payload' => ['channel_id' => $message->communication_channel_id, 'message_id' => $message->id],
                ], $message);
            });

        $mentions->each(function (Mention $mention) {
            $mention->update(['notified_at' => now()]);
        });
    }

    private function notifyAnnouncementAudience(Announcement $announcement): void
    {
        $users = $this->audienceUsers($announcement);
        $users->each(fn (User $user) => $this->notify($user, [
            'notification_type' => 'Announcement',
            'title' => $announcement->title,
            'body' => Str::limit($announcement->body, 160),
            'payload' => ['announcement_id' => $announcement->id, 'priority' => $announcement->priority],
        ], $announcement));
    }

    private function audienceUsers(Announcement $announcement): Collection
    {
        $query = User::query();

        if ($announcement->branch_id || $announcement->department_id) {
            $userIds = DB::table('business_user')
                ->where('business_id', ActiveBusiness::id())
                ->when($announcement->branch_id, fn ($query) => $query->where('branch_id', $announcement->branch_id))
                ->when($announcement->department_id, fn ($query) => $query->where('department_id', $announcement->department_id))
                ->pluck('user_id');

            return $query->whereIn('id', $userIds)->get();
        }

        return $query->whereIn('id', DB::table('business_user')->where('business_id', ActiveBusiness::id())->pluck('user_id'))->get();
    }

    private function recordMentions(Message $message, string $body, User $sender): Collection
    {
        return collect($this->mentionTokens($body))
            ->flatMap(fn (string $token) => $this->createMentions($token, $message, null, $sender))
            ->values();
    }

    private function recordAnnouncementMentions(Announcement $announcement): void
    {
        collect($this->mentionTokens($announcement->body))
            ->each(fn (string $token) => $this->createMentions($token, null, $announcement, $announcement->author));
    }

    private function mentionTokens(string $body): array
    {
        preg_match_all('/@([A-Za-z0-9_.-]+)/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    private function createMentions(string $token, ?Message $message, ?Announcement $announcement, ?User $actor): Collection
    {
        $token = trim($token, '.,:;!?()[]{}');
        $normalized = Str::of($token)->lower()->replace(['.', '_'], '-')->toString();
        $mentions = collect();

        if (strcasecmp($token, 'everyone') === 0) {
            $mentions->push($this->mention($message, $announcement, 'Everyone', null, $token));
        }

        User::whereIn('id', $this->businessUserIds())
            ->where('id', '!=', $actor?->id)
            ->where(fn ($query) => $query->where('name', 'like', str_replace('-', ' ', $normalized).'%')->orWhere('username', $token))
            ->limit(10)
            ->get()
            ->each(fn (User $user) => $mentions->push($this->mention($message, $announcement, 'User', $user->id, $token)));

        Department::where('name', 'like', str_replace('-', ' ', $normalized).'%')
            ->limit(5)
            ->get()
            ->each(fn (Department $department) => $mentions->push($this->mention($message, $announcement, 'Department', $department->id, $token)));

        Branch::where('name', 'like', str_replace('-', ' ', $normalized).'%')
            ->limit(5)
            ->get()
            ->each(fn (Branch $branch) => $mentions->push($this->mention($message, $announcement, 'Branch', $branch->id, $token)));

        if (IamRole::where('business_id', ActiveBusiness::id())->where('slug', $normalized)->exists()) {
            $mentions->push($this->mention($message, $announcement, 'Role', null, $token));
        }

        return $mentions;
    }

    private function mention(?Message $message, ?Announcement $announcement, string $type, ?int $id, string $token): Mention
    {
        return Mention::create([
            'message_id' => $message?->id,
            'announcement_id' => $announcement?->id,
            'mentioned_type' => $type,
            'mentioned_id' => $id,
            'token' => $token,
        ]);
    }

    private function businessMembership(User $user): ?object
    {
        if (! Schema::hasTable('business_user')) {
            return null;
        }

        return DB::table('business_user')
            ->where('business_id', ActiveBusiness::id())
            ->where('user_id', $user->id)
            ->first();
    }

    private function roleSlugFor(User $user): ?string
    {
        $membership = $this->businessMembership($user);

        return $membership?->iam_role_id ? IamRole::whereKey($membership->iam_role_id)->value('slug') : $user->role;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'channel';
        $slug = $base;
        $counter = 2;

        while (CommunicationChannel::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function audit(string $event, Model $subject, array $old = []): void
    {
        CommunicationAuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $subject->getAttributes(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
