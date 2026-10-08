@extends('layouts.app')
@section('title', 'Communication Center')

@section('content')
<style>
    .comm-toolbar{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end;margin-bottom:14px}
    .comm-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:14px}
    .comm-metric{background:#fff;border:1px solid #d9dee8;border-radius:8px;padding:10px}
    .comm-metric span{display:block;color:#667085;font-size:.68rem;font-weight:800;text-transform:uppercase}
    .comm-metric strong{font-size:1.1rem;color:#0f766e}
    .comm-shell{display:grid;grid-template-columns:300px minmax(0,1fr) 340px;gap:12px;align-items:start}
    .comm-panel{background:#fff;border:1px solid #d9dee8;border-radius:8px;min-width:0}
    .comm-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-bottom:1px solid #edf0f5}
    .comm-panel-body{padding:12px 14px}
    .comm-title{font-size:.78rem;font-weight:800;text-transform:uppercase;color:#667085;letter-spacing:0}
    .comm-channel{display:grid;grid-template-columns:36px minmax(0,1fr) auto;gap:10px;align-items:center;border:1px solid #edf0f5;border-radius:8px;padding:9px;text-decoration:none;color:#111827;margin-bottom:8px}
    .comm-channel:hover{border-color:#b8c0cc;background:#fafafa}
    .comm-channel.active{border-color:#00A651;background:#eefaf3}
    .comm-channel.has-unread{border-color:#fca5a5;background:#fff7f7}
    .comm-channel.has-unread.active{border-color:#00A651;background:#eefaf3}
    .comm-channel-name{font-weight:600}
    .comm-channel.has-unread .comm-channel-name{font-weight:800;color:#111827}
    .comm-channel-state{display:inline-flex;align-items:center;gap:4px;margin-top:3px;font-size:.68rem;font-weight:800}
    .comm-channel-state.is-unread{color:#b42318}
    .comm-channel-state.is-read{color:#667085}
    .comm-unread-badge{display:inline-grid;place-items:center;min-width:23px;height:23px;padding:0 6px;border-radius:999px;background:#dc2626;color:#fff;font-size:.72rem;font-weight:900}
    .comm-avatar{width:36px;height:36px;border-radius:8px;background:#0f766e;color:#fff;display:grid;place-items:center;font-weight:800;flex:0 0 auto}
    .comm-main{min-height:650px;display:grid;grid-template-rows:auto minmax(280px,1fr) auto}
    .comm-stream{height:50vh;min-height:320px;overflow:auto;padding:12px 14px;display:flex;flex-direction:column;gap:8px;background:#f8fafc}
    .comm-message{display:flex;align-items:flex-end;gap:8px;width:fit-content;max-width:86%;padding:0;border:0}
    .comm-message.is-mine{margin-left:auto;flex-direction:row-reverse}
    .comm-message.is-mine .comm-avatar{background:#00A651}
    .comm-bubble{min-width:0;max-width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:14px 14px 14px 4px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .comm-message.is-mine .comm-bubble{border-color:#b7e8cb;border-radius:14px 14px 4px 14px;background:#e8f8ef}
    .comm-read-state{display:inline-flex;align-items:center;gap:3px;border-radius:999px;padding:2px 7px;font-size:.62rem;font-weight:900;text-transform:uppercase;letter-spacing:.04em}
    .comm-read-state.is-read{color:#067647;background:#dcfae6}
    .comm-read-state.is-unread{color:#b42318;background:#fee4e2}
    .comm-read-state.is-sent{color:#475467;background:#f2f4f7}
    .comm-message.is-mine .comm-meta{justify-content:flex-end}
    .comm-message.is-mine .comm-meta strong{margin-right:auto}
    .comm-meta{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .comm-text{white-space:pre-wrap;overflow-wrap:anywhere;margin-top:2px}
    .comm-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:8px}
    .comm-message.is-mine .comm-actions{justify-content:flex-end}
    .comm-icon-btn{width:32px;height:32px;border:1px solid #d0d5dd;border-radius:8px;background:#fff;color:#344054;display:inline-grid;place-items:center}
    .comm-icon-btn:hover{background:#f6f8fb}
    .comm-composer{padding:12px 14px;border-top:1px solid #edf0f5;background:#fbfcfd;border-radius:0 0 8px 8px}
    .comm-list{display:grid;gap:8px}
    .comm-line{border:1px solid #edf0f5;border-radius:8px;padding:9px;min-width:0}
    .comm-scroll{max-height:320px;overflow:auto}
    .presence{width:9px;height:9px;border-radius:999px;background:#98a2b3;display:inline-block}
    .presence.Online{background:#12b76a}.presence.Away{background:#f79009}.presence.Busy{background:#f04438}
    .comm-grid-two{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .comm-search-results{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:14px}
    .comm-side[data-defer-template]:empty{min-height:180px}
    .comm-side[data-defer-template]:empty::before{content:"";display:block;height:180px;border:1px solid #edf0f5;border-radius:8px;background:linear-gradient(90deg,#f7f8fb 25%,#eef1f5 37%,#f7f8fb 63%);background-size:400% 100%;animation:comm-skeleton 1.2s ease infinite}
    @keyframes comm-skeleton{0%{background-position:100% 0}100%{background-position:0 0}}
    @media(max-width:1300px){.comm-shell{grid-template-columns:280px minmax(0,1fr)}.comm-side{grid-column:1/-1}.comm-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.comm-search-results{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:760px){.comm-toolbar,.comm-shell,.comm-grid-two{grid-template-columns:1fr}.comm-metrics,.comm-search-results{grid-template-columns:repeat(2,minmax(0,1fr))}.comm-stream{height:44vh;padding:10px}.comm-panel-head{align-items:flex-start;flex-direction:column}.comm-actions .btn{width:100%}.comm-message{max-width:94%;content-visibility:auto;contain-intrinsic-size:1px 92px}.comm-side[data-defer-template]:empty::before{display:none}}
</style>

<div class="page-shell" data-live-updates-url="{{ route('communication.updates') }}" data-notification-url-template="{{ route('communication.notifications.open', ['notification' => '__NOTICE__']) }}" data-attachment-url-template="{{ route('communication.attachments.download', ['attachment' => '__ATTACHMENT__']) }}">
<x-page-header title="Messages" kicker="Shared Workspace" subtitle="Conversations, alerts, files, announcements, and team communication.">
    <x-slot:actions>
    <form method="get" action="{{ route('communication.center') }}" class="d-flex gap-2 flex-wrap">
        @if($activeChannel)<input type="hidden" name="channel" value="{{ $activeChannel->id }}">@endif
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search messages, people, files">
        <button class="btn btn-outline-dark" title="Search"><i class="bi bi-search"></i></button>
    </form>
    </x-slot:actions>
</x-page-header>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="comm-metrics">
    @foreach($metrics as $label => $value)
        <div class="comm-metric"><span>{{ $label }}</span><strong>{{ is_numeric($value) ? number_format($value) : $value }}</strong></div>
    @endforeach
</div>

@if($searchResults)
    <div class="comm-search-results">
        <div class="comm-panel"><div class="comm-panel-body"><div class="comm-title mb-2">Conversations</div>@forelse($searchResults['channels'] as $channel)<a class="d-block" href="{{ route('communication.center', ['channel' => $channel->id, 'q' => request('q')]) }}">{{ $channel->name }}</a>@empty<span class="text-muted small">None</span>@endforelse</div></div>
        <div class="comm-panel"><div class="comm-panel-body"><div class="comm-title mb-2">Messages</div>@forelse($searchResults['messages'] as $message)<div class="small text-truncate">{{ $message->sender?->name }}: {{ $message->body }}</div>@empty<span class="text-muted small">None</span>@endforelse</div></div>
        <div class="comm-panel"><div class="comm-panel-body"><div class="comm-title mb-2">Files</div>@forelse($searchResults['files'] as $file)<div class="small text-truncate">{{ $file->file_name }}</div>@empty<span class="text-muted small">None</span>@endforelse</div></div>
        <div class="comm-panel"><div class="comm-panel-body"><div class="comm-title mb-2">People</div>@forelse($searchResults['users'] as $person)<div class="small text-truncate">{{ $person->name }} <span class="text-muted">{{ $person->email }}</span></div>@empty<span class="text-muted small">None</span>@endforelse</div></div>
    </div>
@endif

<div class="comm-shell">
    <aside class="comm-panel">
        <div class="comm-panel-head">
            <div>
                <div class="comm-title">Conversations</div>
                <strong>{{ $channels->count() }}</strong>
            </div>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('communication.center') }}" title="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
        <div class="comm-panel-body comm-scroll">
            @forelse($channels as $channel)
                <a class="comm-channel {{ $activeChannel?->id === $channel->id ? 'active' : '' }} {{ ($channel->unread_count ?? 0) > 0 ? 'has-unread' : '' }}" data-channel-id="{{ $channel->id }}" href="{{ route('communication.center', ['channel' => $channel->id]) }}">
                    <span class="comm-avatar">{{ strtoupper(substr($channel->name, 0, 1)) }}</span>
                    <span class="min-w-0">
                        <strong class="comm-channel-name d-block text-truncate">{{ $channel->name }}</strong>
                        <small class="text-muted">{{ $channel->type }} / {{ $channel->visibility }}</small>
                        <span class="comm-channel-state {{ ($channel->unread_count ?? 0) > 0 ? 'is-unread' : 'is-read' }}">
                            @if(($channel->unread_count ?? 0) > 0)
                                <i class="bi bi-envelope-exclamation-fill"></i> {{ $channel->unread_count }} unread
                            @else
                                <i class="bi bi-check2-all"></i> All read
                            @endif
                        </span>
                    </span>
                    @if(($channel->unread_count ?? 0) > 0)
                        <span class="comm-unread-badge" aria-label="{{ $channel->unread_count }} unread messages">{{ $channel->unread_count }}</span>
                    @endif
                </a>
            @empty
                <div class="text-muted small">No conversations yet.</div>
            @endforelse
        </div>

        @if(auth()->user()?->hasPermission('communication.create_channel'))
            <div class="comm-panel-body border-top">
                <form method="post" action="{{ route('communication.channels.store') }}" class="comm-list">
                    @csrf
                    <input class="form-control" name="name" placeholder="New conversation" required>
                    <select class="form-select" name="type">
                        @foreach(\Shared\Communication\Models\CommunicationChannel::TYPES as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="visibility">
                        <option>Private</option>
                        <option>Public</option>
                    </select>
                    <textarea class="form-control" name="description" rows="2" placeholder="Topic"></textarea>
                    <select class="form-select" name="department_id">
                        <option value="">Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" name="branch_id">
                        <option value="">Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-success"><i class="bi bi-plus-circle"></i> Create</button>
                </form>
            </div>
        @endif
    </aside>

    <section class="comm-panel comm-main">
        <div class="comm-panel-head">
            <div class="min-w-0">
                <div class="comm-title">{{ $activeChannel?->type ?? 'Conversation' }}</div>
                <h2 class="h5 mb-0 text-truncate">{{ $activeChannel?->name ?? 'No active conversation' }}</h2>
                @if($activeChannel?->description)<small class="text-muted">{{ $activeChannel->description }}</small>@endif
            </div>
            @if($activeChannel)
                <form method="post" action="{{ route('communication.channels.read', $activeChannel) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2-all"></i> Read</button>
                </form>
            @endif
        </div>

        <div class="comm-stream" data-channel-id="{{ $activeChannel?->id }}" data-last-message-id="{{ $messages->last()?->id ?? 0 }}" aria-live="polite">
            @forelse($messages as $message)
                @php
                    $isMyMessage = (int) $message->sender_id === (int) auth()->id();
                    $isMessageRead = $isMyMessage
                        ? $message->reads->contains(fn ($read) => (int) $read->user_id !== (int) auth()->id())
                        : $message->reads->contains(fn ($read) => (int) $read->user_id === (int) auth()->id());
                @endphp
                <article class="comm-message {{ $isMyMessage ? 'is-mine' : 'is-other' }}" id="message-{{ $message->id }}">
                    <span class="comm-avatar">{{ strtoupper(substr($message->sender?->name ?? 'S', 0, 1)) }}</span>
                    <div class="comm-bubble">
                        <div class="comm-meta">
                            <strong>{{ $message->sender?->name ?? 'System' }}</strong>
                            <small class="text-muted">
                                {{ $message->created_at->format('M j, H:i') }}
                                <span class="comm-read-state {{ $isMyMessage ? ($isMessageRead ? 'is-read' : 'is-sent') : ($isMessageRead ? 'is-read' : 'is-unread') }}">
                                    @if($isMyMessage)
                                        <i class="bi {{ $isMessageRead ? 'bi-check2-all' : 'bi-check' }}"></i> {{ $isMessageRead ? 'Read' : 'Sent' }}
                                    @else
                                        <i class="bi {{ $isMessageRead ? 'bi-check2-all' : 'bi-envelope' }}"></i> {{ $isMessageRead ? 'Read' : 'Unread' }}
                                    @endif
                                </span>
                                @if($message->edited_at)
                                    / edited
                                @endif
                            </small>
                        </div>
                        @if($message->parent)
                            <div class="small text-muted border-start ps-2 mt-1">{{ $message->parent->sender?->name }}: {{ \Illuminate\Support\Str::limit($message->parent->body, 90) }}</div>
                        @endif
                        <div class="comm-text">{{ $message->body }}</div>
                        @if($message->attachments->isNotEmpty())
                            <div class="comm-actions">
                                @foreach($message->attachments as $attachment)
                                    <a class="btn btn-sm btn-light border" href="{{ route('communication.attachments.download', $attachment) }}">
                                        <i class="bi {{ $attachment->is_voice_note ? 'bi-mic' : 'bi-paperclip' }}"></i> {{ $attachment->file_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <div class="comm-actions">
                            <form method="post" action="{{ route('communication.messages.reactions.store', $message) }}">
                                @csrf
                                <input type="hidden" name="reaction" value="+1">
                                <button class="comm-icon-btn" title="React"><i class="bi bi-hand-thumbs-up"></i></button>
                            </form>
                            <form method="post" action="{{ route('communication.messages.save', $message) }}">
                                @csrf
                                <button class="comm-icon-btn" title="Save"><i class="bi bi-bookmark"></i></button>
                            </form>
                            @if(auth()->user()?->hasPermission('communication.manage_channel'))
                                <form method="post" action="{{ route('communication.messages.pin', $message) }}">
                                    @csrf
                                    <button class="comm-icon-btn" title="Pin"><i class="bi bi-pin-angle"></i></button>
                                </form>
                            @endif
                            @if(auth()->user()?->hasPermission('communication.delete_own'))
                                <form method="post" action="{{ route('communication.messages.destroy', $message) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="comm-icon-btn" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                            <span class="small text-muted">{{ $message->reactions->count() }} reactions / {{ $message->reads->count() }} reads</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="text-muted p-3">No messages in this conversation.</div>
            @endforelse
        </div>

        <div class="comm-composer">
            <form method="post" action="{{ route('communication.messages.store') }}" enctype="multipart/form-data" class="comm-list">
                @csrf
                <input type="hidden" name="channel_id" value="{{ $activeChannel?->id }}">
                <textarea class="form-control" name="body" rows="3" placeholder="Write a message or @mention a teammate" required></textarea>
                <div class="d-flex gap-2 flex-wrap">
                    <input class="form-control" type="file" name="attachments[]" multiple @disabled(!$settings->allow_file_sharing)>
                    <button class="btn btn-success" @disabled(!$activeChannel || !$settings->chat_enabled)><i class="bi bi-send"></i> Send</button>
                </div>
            </form>
        </div>
    </section>

    <aside class="comm-side comm-list" data-defer-template="comm-side-template" aria-live="polite"></aside>
    <template id="comm-side-template">
        <div class="comm-list">
        <section class="comm-panel">
            <div class="comm-panel-head"><div><div class="comm-title">Directory</div><strong>{{ $users->count() }} people</strong></div></div>
            <div class="comm-panel-body">
                <form method="get" action="{{ route('communication.center') }}" class="d-flex gap-2 mb-2">
                    @if($activeChannel)<input type="hidden" name="channel" value="{{ $activeChannel->id }}">@endif
                    <input class="form-control" name="people" value="{{ request('people') }}" placeholder="Find employee">
                    <button class="btn btn-outline-dark" title="Find"><i class="bi bi-search"></i></button>
                </form>
                <div class="comm-scroll comm-list">
                    @foreach($users as $person)
                        <div class="comm-line">
                            <div class="d-flex justify-content-between gap-2">
                                <strong class="text-truncate"><span class="presence {{ $person['presence_status'] }}"></span> {{ $person['name'] }}</strong>
                                <small class="text-muted">{{ $person['role'] }}</small>
                            </div>
                            <div class="small text-muted">{{ $person['department'] ?: 'No department' }} / {{ $person['branch'] ?: 'No branch' }}</div>
                            @if($person['id'] !== auth()->id())
                                <form method="post" action="{{ route('communication.messages.store') }}" class="d-flex gap-2 mt-2">
                                    @csrf
                                    <input type="hidden" name="recipient_id" value="{{ $person['id'] }}">
                                    <input class="form-control form-control-sm" name="body" placeholder="Direct message" required>
                                    <button class="btn btn-sm btn-outline-success" title="Send"><i class="bi bi-send"></i></button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="comm-panel">
            <div class="comm-panel-head"><div><div class="comm-title">Announcements</div><strong>{{ $announcements->count() }}</strong></div></div>
            <div class="comm-panel-body comm-list">
                @forelse($announcements as $announcement)
                    <div class="comm-line">
                        <div class="d-flex justify-content-between gap-2"><strong>{{ $announcement->title }}</strong><span class="badge text-bg-{{ $announcement->priority === 'Critical' ? 'danger' : ($announcement->priority === 'High' ? 'warning' : 'secondary') }}">{{ $announcement->priority }}</span></div>
                        <div class="small text-muted">{{ $announcement->scope_type }}</div>
                        <p class="small mb-2">{{ \Illuminate\Support\Str::limit($announcement->body, 130) }}</p>
                        @if($announcement->requires_acknowledgement)
                            <form method="post" action="{{ route('communication.announcements.acknowledge', $announcement) }}">
                                @csrf
                                <input type="hidden" name="acknowledge" value="1">
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2-circle"></i> Acknowledge</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-muted small">No announcements.</div>
                @endforelse

                @if(auth()->user()?->hasPermission('communication.announcements.create'))
                    <form method="post" action="{{ route('communication.announcements.store') }}" class="comm-list border-top pt-3">
                        @csrf
                        <input class="form-control" name="title" placeholder="Announcement title" required>
                        <textarea class="form-control" name="body" rows="3" placeholder="Announcement body" required></textarea>
                        <div class="comm-grid-two">
                            <select class="form-select" name="scope_type"><option>Company</option><option>Branch</option><option>Department</option><option>Industry</option></select>
                            <select class="form-select" name="priority"><option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option></select>
                        </div>
                        <div class="comm-grid-two">
                            <select class="form-select" name="department_id"><option value="">Department</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select>
                            <select class="form-select" name="branch_id"><option value="">Branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
                        </div>
                        <label class="small"><input type="checkbox" name="requires_acknowledgement" value="1"> Require acknowledgement</label>
                        <button class="btn btn-warning"><i class="bi bi-megaphone"></i> Publish</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="comm-panel">
            <div class="comm-panel-head"><div><div class="comm-title">Pinned</div><strong>{{ $pinnedMessages->count() }}</strong></div></div>
            <div class="comm-panel-body comm-list">
                @forelse($pinnedMessages as $pin)
                    <div class="comm-line small">{{ $pin->message?->sender?->name }}: {{ \Illuminate\Support\Str::limit($pin->message?->body, 90) }}</div>
                @empty
                    <span class="text-muted small">No pinned messages.</span>
                @endforelse
            </div>
        </section>

        <section class="comm-panel">
            <div class="comm-panel-head"><div><div class="comm-title">Saved</div><strong>{{ $savedMessages->count() }}</strong></div></div>
            <div class="comm-panel-body comm-list">
                @forelse($savedMessages as $saved)
                    <div class="comm-line small">
                        <div>{{ $saved->message?->channel?->name }}</div>
                        <strong>{{ \Illuminate\Support\Str::limit($saved->message?->body, 85) }}</strong>
                    </div>
                @empty
                    <span class="text-muted small">No saved messages.</span>
                @endforelse
            </div>
        </section>

        <section class="comm-panel">
            <div class="comm-panel-head"><div><div class="comm-title">Files</div><strong>{{ $sharedFiles->count() }}</strong></div></div>
            <div class="comm-panel-body comm-list">
                @forelse($sharedFiles as $file)
                    <a class="comm-line small text-decoration-none text-dark" href="{{ route('communication.attachments.download', $file) }}"><i class="bi bi-paperclip"></i> {{ $file->file_name }}</a>
                @empty
                    <span class="text-muted small">No files shared.</span>
                @endforelse
            </div>
        </section>

        @if(auth()->user()?->hasPermission('communication.settings'))
            <section class="comm-panel">
                <div class="comm-panel-head"><div><div class="comm-title">Settings</div><strong>Controls</strong></div></div>
                <div class="comm-panel-body">
                    <form method="post" action="{{ route('communication.settings.update') }}" class="comm-list">
                        @csrf
                        @method('PUT')
                        @foreach(['chat_enabled' => 'Chat', 'allow_direct_messages' => 'Direct messages', 'allow_employee_group_creation' => 'Employee groups', 'allow_file_sharing' => 'File sharing', 'allow_message_editing' => 'Editing', 'allow_message_deletion' => 'Deletion', 'enable_read_receipts' => 'Read receipts', 'enable_presence' => 'Presence', 'enable_typing_indicators' => 'Typing', 'allow_everyone_mentions' => 'Mass mentions'] as $field => $label)
                            <label class="small d-flex justify-content-between"><span>{{ $label }}</span><input type="checkbox" name="{{ $field }}" value="1" @checked($settings->{$field})></label>
                        @endforeach
                        <div class="comm-grid-two">
                            <label class="small">Attachment KB<input class="form-control" type="number" name="max_attachment_size_kb" value="{{ $settings->max_attachment_size_kb }}" min="128" max="102400"></label>
                            <label class="small">Edit minutes<input class="form-control" type="number" name="message_edit_time_limit_minutes" value="{{ $settings->message_edit_time_limit_minutes }}" min="1" max="10080"></label>
                        </div>
                        <label class="small">Retention days<input class="form-control" type="number" name="message_retention_days" value="{{ $settings->message_retention_days }}" min="1" max="3650"></label>
                        <button class="btn btn-outline-dark"><i class="bi bi-sliders"></i> Save Settings</button>
                    </form>
                </div>
            </section>
        @endif
        </div>
    </template>
</div>
</div>
@push('scripts')
<script>
(() => {
    const page = document.querySelector('.page-shell[data-live-updates-url]');
    const stream = document.querySelector('.comm-stream[data-channel-id]');
    if (!page || !stream || !stream.dataset.channelId) return;

    const updatesUrl = page.dataset.liveUpdatesUrl;
    let lastMessageId = Number(stream.dataset.lastMessageId || 0);
    let polling = false;

    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    };

    const appendMessage = message => {
        if (!message || document.getElementById(`message-${message.id}`)) return;
        stream.querySelector('.text-muted.p-3')?.remove();
        const nearBottom = stream.scrollHeight - stream.scrollTop - stream.clientHeight < 100;
        const mine = Number(message.sender_id) === Number(@json(auth()->id()));
        const article = element('article', `comm-message ${mine ? 'is-mine' : 'is-other'}`);
        article.id = `message-${message.id}`;
        article.dataset.messageId = message.id;

        const name = message.sender?.name || 'System';
        article.append(element('span', 'comm-avatar', name.trim().charAt(0).toUpperCase() || 'S'));
        const bubble = element('div', 'comm-bubble');
        const meta = element('div', 'comm-meta');
        meta.append(element('strong', '', name));
        const time = element('small', 'text-muted');
        const date = new Date(message.created_at);
        time.append(document.createTextNode(Number.isNaN(date.getTime()) ? '' : date.toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'})));
        const state = element('span', `comm-read-state ${mine ? 'is-sent' : 'is-unread'}`);
        state.append(element('i', `bi ${mine ? 'bi-check' : 'bi-envelope'}`), document.createTextNode(mine ? ' Sent' : ' Unread'));
        time.append(document.createTextNode(' '), state);
        meta.append(time);
        bubble.append(meta, element('div', 'comm-text', message.body || ''));

        const attachments = Array.isArray(message.attachments) ? message.attachments : [];
        if (attachments.length) {
            const links = element('div', 'comm-actions');
            attachments.forEach(attachment => {
                const link = element('a', 'btn btn-sm btn-light border', attachment.file_name || 'Attachment');
                link.href = page.dataset.attachmentUrlTemplate.replace('__ATTACHMENT__', attachment.id);
                links.append(link);
            });
            bubble.append(links);
        }
        bubble.append(element('div', 'comm-actions', `${(message.reactions || []).length} reactions / ${(message.reads || []).length} reads`));
        article.append(bubble);
        stream.append(article);
        lastMessageId = Math.max(lastMessageId, Number(message.id) || 0);
        stream.dataset.lastMessageId = String(lastMessageId);
        if (nearBottom) stream.scrollTop = stream.scrollHeight;
        if (!mine) showMessageAlert(name, message.body || 'New message');
    };

    const showMessageAlert = (sender, body) => {
        document.querySelector('[data-live-message-alert]')?.remove();
        const notice = element('div', 'alert alert-success position-fixed shadow-sm');
        notice.dataset.liveMessageAlert = 'true';
        notice.setAttribute('role', 'status');
        notice.style.cssText = 'right:18px;bottom:18px;z-index:1090;max-width:min(380px,calc(100vw - 36px))';
        notice.textContent = `${sender}: ${body}`;
        document.body.append(notice);
        window.setTimeout(() => notice.remove(), 6000);
    };

    const updateChannelUnread = counts => {
        document.querySelectorAll('.comm-channel[data-channel-id]').forEach(link => {
            const count = Number(counts?.[link.dataset.channelId] || 0);
            link.classList.toggle('has-unread', count > 0);
            let badge = link.querySelector('.comm-unread-badge');
            if (count > 0) {
                if (!badge) {
                    badge = element('span', 'comm-unread-badge');
                    badge.setAttribute('aria-label', `${count} unread messages`);
                    link.append(badge);
                }
                badge.textContent = String(count);
                badge.setAttribute('aria-label', `${count} unread messages`);
            } else {
                badge?.remove();
            }
            const state = link.querySelector('.comm-channel-state');
            if (state) {
                state.classList.toggle('is-unread', count > 0);
                state.classList.toggle('is-read', count === 0);
                state.replaceChildren(element('i', count > 0 ? 'bi bi-envelope-exclamation-fill' : 'bi bi-check2-all'), document.createTextNode(count > 0 ? ` ${count} unread` : ' All read'));
            }
        });
    };

    const updateHeaderAlerts = data => {
        const button = document.querySelector('.header-alert-btn');
        const menu = document.querySelector('.header-notification-menu');
        if (!button || !menu) return;
        const total = Number(data.unread_total || 0);
        let badge = button.querySelector('.header-badge');
        if (total > 0) {
            if (!badge) { badge = element('span', 'header-badge'); button.append(badge); }
            badge.textContent = total > 99 ? '99+' : String(total);
        } else badge?.remove();

        const header = menu.querySelector('.header-notification-head');
        const summary = header?.querySelector('.text-muted.small');
        if (summary) summary.textContent = `${Number(data.unread_messages || 0).toLocaleString()} unread message${Number(data.unread_messages || 0) === 1 ? '' : 's'}`;
        const totalBadge = header?.querySelector('.badge');
        if (totalBadge) totalBadge.textContent = total.toLocaleString();

        const list = menu.querySelector('.header-notification-list');
        if (!list) return;
        list.replaceChildren();
        const notices = Array.isArray(data.notifications) ? data.notifications : [];
        if (!notices.length) {
            list.append(element('div', 'p-3 text-muted', 'No new messages or alerts.'));
            return;
        }
        notices.forEach(notice => {
            const link = element('a', 'header-notification-item');
            link.href = page.dataset.notificationUrlTemplate.replace('__NOTICE__', notice.id);
            const title = element('div', 'notification-title');
            const label = element('span');
            label.append(element('i', `bi ${['Message', 'Mention'].includes(notice.notification_type) ? 'bi-chat-dots' : 'bi-bell'} me-1 text-success`), document.createTextNode(notice.title || 'New alert'));
            title.append(label, element('small', 'text-muted', notice.created_at ? new Date(notice.created_at).toLocaleString() : ''));
            link.append(title);
            if (notice.body) link.append(element('div', 'notification-body', notice.body));
            list.append(link);
        });
    };

    const refresh = async () => {
        if (polling || document.hidden) return;
        polling = true;
        try {
            const url = new URL(updatesUrl, window.location.origin);
            url.searchParams.set('channel_id', stream.dataset.channelId);
            url.searchParams.set('after_id', String(lastMessageId));
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store', credentials: 'same-origin'});
            if (!response.ok) return;
            const data = await response.json();
            (data.messages || []).forEach(appendMessage);
            updateChannelUnread(data.unread_by_channel || {});
            updateHeaderAlerts(data);
        } catch (_) {
            // Retry when the connection is available again.
        } finally { polling = false; }
    };

    const composer = document.querySelector('.comm-composer form');
    composer?.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = composer.querySelector('button[type="submit"], button:not([type])');
        if (submit?.disabled) return;
        if (submit) submit.disabled = true;
        try {
            const response = await fetch(composer.action, {method: 'POST', body: new FormData(composer), headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Message could not be sent.');
            appendMessage(result.message);
            composer.querySelector('textarea[name="body"]')?.value && (composer.querySelector('textarea[name="body"]').value = '');
            composer.querySelector('input[type="file"]')?.value && (composer.querySelector('input[type="file"]').value = '');
            await refresh();
        } catch (error) {
            showMessageAlert('Message not sent', error.message || 'Please try again.');
        } finally { if (submit) submit.disabled = false; }
    });

    document.querySelector('.comm-main .comm-panel-head form')?.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            const form = event.currentTarget;
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (response.ok) await refresh();
        } catch (_) { /* The unread badge remains until the next poll. */ }
    });

    stream.scrollTop = stream.scrollHeight;
    refresh();
    window.setInterval(refresh, 4000);
    document.addEventListener('visibilitychange', refresh);
})();
</script>
@endpush
@endsection
