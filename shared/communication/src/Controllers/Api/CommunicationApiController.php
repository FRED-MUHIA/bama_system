<?php

namespace Shared\Communication\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Shared\Communication\Contracts\CommunicationServiceContract;
use Shared\Communication\Models\Announcement;
use Shared\Communication\Models\CommunicationChannel;
use Shared\Communication\Models\CommunicationNotification;
use Shared\Communication\Models\Message;

class CommunicationApiController extends Controller
{
    public function channels(Request $request, CommunicationServiceContract $communication)
    {
        $channels = $communication->accessibleChannels($request->user())
            ->when($request->query('type'), fn ($channels, $type) => $channels->where('type', $type))
            ->map(function (CommunicationChannel $channel) use ($request) {
                $unread = CommunicationNotification::where('user_id', $request->user()->id)
                    ->where('status', 'Unread')
                    ->where('payload->channel_id', $channel->id)
                    ->count();

                return $channel->setAttribute('unread_count', $unread);
            })
            ->values();

        return response()->json(['data' => $channels]);
    }

    public function storeChannel(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(CommunicationChannel::TYPES)],
            'visibility' => ['nullable', 'in:Private,Public'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'industry' => ['nullable', 'string', 'max:80'],
            'role_slug' => ['nullable', 'string', 'max:100'],
            'record_type' => ['nullable', 'string', 'max:255'],
            'record_id' => ['nullable', 'integer', 'min:1'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
            'settings' => ['nullable', 'array'],
        ]);

        return response()->json(['data' => $communication->createChannel($data, $request->user())], 201);
    }

    public function messages(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'channel_id' => ['required', 'exists:communication_channels,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'before_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $channel = CommunicationChannel::findOrFail($data['channel_id']);
        abort_unless($communication->accessibleChannels($request->user())->contains('id', $channel->id), 403);

        $messages = Message::with('sender', 'attachments', 'reactions', 'mentions', 'reads', 'parent.sender')
            ->where('communication_channel_id', $channel->id)
            ->whereDoesntHave('deletions', fn ($deletions) => $deletions->where('user_id', $request->user()->id))
            ->when($data['q'] ?? null, fn ($query, $q) => $query->where('body', 'like', '%'.$q.'%'))
            ->when($data['before_id'] ?? null, fn ($query, $id) => $query->where('id', '<', $id))
            ->latest()
            ->limit($data['limit'] ?? 50)
            ->get()
            ->reverse()
            ->values();

        return response()->json(['data' => $messages]);
    }

    public function storeMessage(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'channel_id' => ['nullable', 'required_without:recipient_id', 'exists:communication_channels,id'],
            'recipient_id' => ['nullable', 'required_without:channel_id', 'exists:users,id'],
            'body' => ['required', 'string'],
            'message_type' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:messages,id'],
            'related_type' => ['nullable', 'string', 'max:255'],
            'related_id' => ['nullable', 'integer', 'min:1'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.file_name' => ['required_with:attachments', 'string', 'max:255'],
            'attachments.*.mime_type' => ['nullable', 'string', 'max:255'],
            'attachments.*.file_size' => ['nullable', 'integer', 'min:0'],
            'attachments.*.path' => ['nullable', 'string', 'max:500'],
            'attachments.*.document_media_id' => ['nullable', 'integer', 'min:1'],
            'attachments.*.disk' => ['nullable', 'string', 'max:80'],
            'attachments.*.is_voice_note' => ['nullable', 'boolean'],
        ]);

        $channel = ! empty($data['recipient_id'])
            ? $communication->directChannel($request->user(), User::findOrFail($data['recipient_id']))
            : CommunicationChannel::findOrFail($data['channel_id']);

        return response()->json(['data' => $communication->sendMessage($channel, $request->user(), $data)], 201);
    }

    public function announcements(Request $request, CommunicationServiceContract $communication)
    {
        $announcements = $communication->accessibleAnnouncements($request->user())
            ->when($request->query('scope_type'), fn ($query, $scope) => $query->where('scope_type', $scope))
            ->values();

        return response()->json(['data' => $announcements]);
    }

    public function storeAnnouncement(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'communication_channel_id' => ['nullable', 'exists:communication_channels,id'],
            'scope_type' => ['required', 'in:Company,Branch,Department,Industry'],
            'scope_id' => ['nullable', 'integer', 'min:1'],
            'industry' => ['nullable', 'string', 'max:80'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'priority' => ['required', Rule::in(Announcement::PRIORITIES)],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
        ]);

        return response()->json(['data' => $communication->publishAnnouncement($data, $request->user())], 201);
    }

    public function notifications(Request $request)
    {
        $notifications = CommunicationNotification::where('user_id', $request->user()->id)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->limit(100)
            ->get();

        return response()->json(['data' => $notifications]);
    }

    public function storeNotification(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'notification_type' => ['required', 'in:Message,Mention,Task,Approval,Alert,Reminder,Announcement'],
            'delivery_channel' => ['nullable', 'in:In-App,Push,Email,SMS'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'action_url' => ['nullable', 'string', 'max:500'],
            'payload' => ['nullable', 'array'],
        ]);

        $user = User::findOrFail($data['user_id']);

        return response()->json(['data' => $communication->notify($user, $data)], 201);
    }

    public function directory(Request $request, CommunicationServiceContract $communication)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(['data' => $communication->employeeDirectory($request->user(), $filters)]);
    }

    public function search(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(['data' => $communication->search($request->user(), $data['q'])]);
    }

    public function markRead(Request $request, CommunicationServiceContract $communication, CommunicationChannel $channel)
    {
        $message = $request->filled('message_id') ? Message::findOrFail($request->integer('message_id')) : null;
        $communication->markRead($channel, $request->user(), $message);

        return response()->json(['status' => 'ok']);
    }

    public function react(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $reaction = $request->validate([
            'reaction' => ['required', 'string', 'max:80'],
        ])['reaction'];

        return response()->json(['data' => $communication->react($message, $request->user(), $reaction)], 201);
    }

    public function save(CommunicationServiceContract $communication, Message $message)
    {
        return response()->json(['data' => $communication->saveMessage($message, request()->user())], 201);
    }

    public function unsave(CommunicationServiceContract $communication, Message $message)
    {
        $communication->unsaveMessage($message, request()->user());

        return response()->json(['status' => 'ok']);
    }

    public function pin(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return response()->json(['data' => $communication->pinMessage($message, $request->user(), $data['note'] ?? null)], 201);
    }

    public function unpin(CommunicationServiceContract $communication, Message $message)
    {
        $communication->unpinMessage($message, request()->user());

        return response()->json(['status' => 'ok']);
    }

    public function updateMessage(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $body = $request->validate(['body' => ['required', 'string']])['body'];

        return response()->json(['data' => $communication->editMessage($message, $request->user(), $body)]);
    }

    public function deleteMessage(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $data = $request->validate([
            'for_everyone' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $communication->deleteMessage($message, $request->user(), (bool) ($data['for_everyone'] ?? false), $data['reason'] ?? null);

        return response()->json(['status' => 'ok']);
    }

    public function acknowledge(Request $request, CommunicationServiceContract $communication, Announcement $announcement)
    {
        $communication->acknowledgeAnnouncement($announcement, $request->user(), $request->boolean('acknowledge'));

        return response()->json(['status' => 'ok']);
    }

    public function settings(CommunicationServiceContract $communication)
    {
        return response()->json(['data' => $communication->settings()]);
    }

    public function updateSettings(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'chat_enabled' => ['nullable', 'boolean'],
            'allow_direct_messages' => ['nullable', 'boolean'],
            'allow_employee_group_creation' => ['nullable', 'boolean'],
            'allow_file_sharing' => ['nullable', 'boolean'],
            'max_attachment_size_kb' => ['required', 'integer', 'min:128', 'max:102400'],
            'allow_message_editing' => ['nullable', 'boolean'],
            'message_edit_time_limit_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'allow_message_deletion' => ['nullable', 'boolean'],
            'enable_read_receipts' => ['nullable', 'boolean'],
            'enable_presence' => ['nullable', 'boolean'],
            'enable_typing_indicators' => ['nullable', 'boolean'],
            'allow_everyone_mentions' => ['nullable', 'boolean'],
            'auto_department_channels' => ['nullable', 'boolean'],
            'auto_team_channels' => ['nullable', 'boolean'],
            'auto_branch_channels' => ['nullable', 'boolean'],
            'message_retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        return response()->json(['data' => $communication->updateSettings($data, $request->user())]);
    }

    public function context(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'record_type' => ['required', 'string', 'max:80'],
            'record_id' => ['required', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $record = $this->contextRecord($data['record_type'], (int) $data['record_id']);

        return response()->json(['data' => $communication->contextChannel($record, $request->user(), $data['name'] ?? null)], 201);
    }

    private function contextRecord(string $type, int $id): Model
    {
        $map = [
            'client' => Client::class,
            'invoice' => Invoice::class,
            'pos-order' => PosOrder::class,
            'project' => Project::class,
            'purchase-order' => PurchaseOrder::class,
            'quotation' => Quotation::class,
            'agriculture-farm' => \Modules\Agriculture\Models\Farm::class,
            'agriculture-field' => \Modules\Agriculture\Models\Field::class,
            'real-estate-property' => \Modules\RealEstate\Models\Property::class,
            'real-estate-lease' => \Modules\RealEstate\Models\Lease::class,
            'real-estate-service-request' => \Modules\RealEstate\Models\ServiceRequest::class,
            'hospitality-reservation' => \Modules\Hospitality\Models\Reservation::class,
            'hospitality-room' => \Modules\Hospitality\Models\Room::class,
        ];

        abort_unless(isset($map[$type]) && class_exists($map[$type]), 422, 'Unsupported communication context.');

        return $map[$type]::findOrFail($id);
    }
}
