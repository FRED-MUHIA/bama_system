<?php

namespace Shared\Communication\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Shared\Communication\Contracts\CommunicationServiceContract;
use Shared\Communication\Models\Announcement;
use Shared\Communication\Models\CommunicationChannel;
use Shared\Communication\Models\MessageAttachment;
use Shared\Communication\Models\Message;

class CommunicationCenterController extends Controller
{
    public function index(Request $request, CommunicationServiceContract $communication)
    {
        $communication->ensureRoleChannels();
        $channels = $communication->accessibleChannels($request->user());
        $activeChannel = $request->query('channel')
            ? $channels->firstWhere('id', (int) $request->query('channel'))
            : $channels->first();

        return view('communication.center', [
            'metrics' => $communication->metrics($request->user()),
            'channels' => $channels,
            'activeChannel' => $activeChannel,
            'messages' => $activeChannel ? Message::with('sender', 'attachments', 'reactions', 'reads', 'parent.sender')
                ->where('communication_channel_id', $activeChannel->id)
                ->whereDoesntHave('deletions', fn ($deletions) => $deletions->where('user_id', $request->user()->id))
                ->latest()
                ->limit(50)
                ->get()
                ->reverse()
                ->values() : collect(),
            'announcements' => $communication->accessibleAnnouncements($request->user(), 10),
            'users' => $communication->employeeDirectory($request->user(), ['q' => $request->query('people')]),
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'settings' => $communication->settings(),
            'searchResults' => $request->query('q') ? $communication->search($request->user(), $request->query('q')) : null,
            'savedMessages' => \Shared\Communication\Models\SavedMessage::with('message.sender', 'message.channel')->where('user_id', $request->user()->id)->latest()->limit(12)->get(),
            'pinnedMessages' => $activeChannel ? $activeChannel->pins()->with('message.sender', 'pinnedBy')->latest()->limit(10)->get() : collect(),
            'sharedFiles' => $activeChannel ? MessageAttachment::with('message.sender')->whereHas('message', fn ($messages) => $messages->where('communication_channel_id', $activeChannel->id))->latest()->limit(12)->get() : collect(),
        ]);
    }

    public function channel(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(CommunicationChannel::TYPES)],
            'visibility' => ['nullable', 'in:Private,Public'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'role_slug' => ['nullable', 'string', 'max:100'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $channel = $communication->createChannel($data, $request->user());

        return redirect()->route('communication.center', ['channel' => $channel->id])->with('status', 'Conversation created.');
    }

    public function message(Request $request, CommunicationServiceContract $communication)
    {
        $data = $request->validate([
            'channel_id' => ['nullable', 'required_without:recipient_id', 'exists:communication_channels,id'],
            'recipient_id' => ['nullable', 'required_without:channel_id', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:messages,id'],
            'body' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,csv,png,jpg,jpeg,webp,mp3,m4a,wav,webm', 'max:10240'],
        ]);

        $channel = ! empty($data['recipient_id'])
            ? $communication->directChannel($request->user(), User::findOrFail($data['recipient_id']))
            : CommunicationChannel::findOrFail($data['channel_id']);

        $data['attachments'] = $this->storedAttachments($request);
        $communication->sendMessage($channel, $request->user(), $data);

        return redirect()->route('communication.center', ['channel' => $channel->id])->with('status', 'Message sent.');
    }

    public function announcement(Request $request, CommunicationServiceContract $communication)
    {
        $communication->publishAnnouncement($request->validate([
            'scope_type' => ['required', 'in:Company,Branch,Department,Industry'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'industry' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
        ]), $request->user());

        return back()->with('status', 'Announcement published.');
    }

    public function markRead(CommunicationServiceContract $communication, CommunicationChannel $channel)
    {
        $communication->markRead($channel, request()->user());

        return back()->with('status', 'Conversation marked read.');
    }

    public function react(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $communication->react($message, $request->user(), $request->validate([
            'reaction' => ['required', 'string', 'max:80'],
        ])['reaction']);

        return back()->with('status', 'Reaction added.');
    }

    public function save(CommunicationServiceContract $communication, Message $message)
    {
        $communication->saveMessage($message, request()->user());

        return back()->with('status', 'Message saved.');
    }

    public function unsave(CommunicationServiceContract $communication, Message $message)
    {
        $communication->unsaveMessage($message, request()->user());

        return back()->with('status', 'Message removed from saved.');
    }

    public function pin(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $communication->pinMessage($message, $request->user(), $request->input('note'));

        return back()->with('status', 'Message pinned.');
    }

    public function unpin(CommunicationServiceContract $communication, Message $message)
    {
        $communication->unpinMessage($message, request()->user());

        return back()->with('status', 'Message unpinned.');
    }

    public function updateMessage(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $communication->editMessage($message, $request->user(), $request->validate([
            'body' => ['required', 'string'],
        ])['body']);

        return back()->with('status', 'Message updated.');
    }

    public function deleteMessage(Request $request, CommunicationServiceContract $communication, Message $message)
    {
        $communication->deleteMessage(
            $message,
            $request->user(),
            $request->boolean('for_everyone'),
            $request->input('reason')
        );

        return back()->with('status', 'Message deleted.');
    }

    public function acknowledge(Request $request, CommunicationServiceContract $communication, Announcement $announcement)
    {
        $communication->acknowledgeAnnouncement($announcement, $request->user(), $request->boolean('acknowledge'));

        return back()->with('status', 'Announcement updated.');
    }

    public function settings(Request $request, CommunicationServiceContract $communication)
    {
        $communication->updateSettings($request->validate([
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
        ]), $request->user());

        return back()->with('status', 'Communication settings updated.');
    }

    public function downloadAttachment(CommunicationServiceContract $communication, MessageAttachment $attachment)
    {
        $attachment = $communication->attachmentForDownload($attachment, request()->user());
        abort_unless($attachment->path && Storage::disk($attachment->disk ?: 'local')->exists($attachment->path), 404);

        return Storage::disk($attachment->disk ?: 'local')->download($attachment->path, $attachment->file_name);
    }

    private function storedAttachments(Request $request): array
    {
        return collect($request->file('attachments', []))
            ->map(function ($file) {
                $path = $file->store('communication/'.now()->format('Y/m'), 'local');

                return [
                    'disk' => 'local',
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'path' => $path,
                    'is_voice_note' => str_starts_with((string) $file->getMimeType(), 'audio/'),
                ];
            })
            ->all();
    }
}
