@extends('layouts.app')
@section('title', 'Communication Center')

@section('content')
<style>
    .comm-toolbar{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end;margin-bottom:14px}
    .comm-metrics{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;margin-bottom:14px}
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
    .comm-avatar{width:36px;height:36px;border-radius:8px;background:#0f766e;color:#fff;display:grid;place-items:center;font-weight:800;flex:0 0 auto}
    .comm-main{min-height:650px;display:grid;grid-template-rows:auto minmax(280px,1fr) auto}
    .comm-stream{height:50vh;min-height:320px;overflow:auto;padding:6px 14px}
    .comm-message{display:grid;grid-template-columns:36px minmax(0,1fr);gap:10px;padding:10px 0;border-bottom:1px solid #f1f3f6}
    .comm-message:last-child{border-bottom:0}
    .comm-bubble{min-width:0}
    .comm-meta{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .comm-text{white-space:pre-wrap;overflow-wrap:anywhere;margin-top:2px}
    .comm-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:8px}
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
    @media(max-width:1300px){.comm-shell{grid-template-columns:280px minmax(0,1fr)}.comm-side{grid-column:1/-1}.comm-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}.comm-search-results{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:760px){.comm-toolbar,.comm-shell,.comm-grid-two{grid-template-columns:1fr}.comm-metrics,.comm-search-results{grid-template-columns:1fr}.comm-stream{height:44vh}.comm-panel-head{align-items:flex-start;flex-direction:column}.comm-actions .btn{width:100%}.comm-message{content-visibility:auto;contain-intrinsic-size:1px 92px}.comm-metric:nth-child(n+4){display:none}.comm-side[data-defer-template]:empty::before{display:none}}
</style>

<div class="page-shell">
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
                <a class="comm-channel {{ $activeChannel?->id === $channel->id ? 'active' : '' }}" href="{{ route('communication.center', ['channel' => $channel->id]) }}">
                    <span class="comm-avatar">{{ strtoupper(substr($channel->name, 0, 1)) }}</span>
                    <span class="min-w-0">
                        <strong class="d-block text-truncate">{{ $channel->name }}</strong>
                        <small class="text-muted">{{ $channel->type }} / {{ $channel->visibility }}</small>
                    </span>
                    @if(($channel->unread_count ?? 0) > 0)<span class="badge bg-success">{{ $channel->unread_count }}</span>@endif
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

        <div class="comm-stream">
            @forelse($messages as $message)
                <article class="comm-message" id="message-{{ $message->id }}">
                    <span class="comm-avatar">{{ strtoupper(substr($message->sender?->name ?? 'S', 0, 1)) }}</span>
                    <div class="comm-bubble">
                        <div class="comm-meta">
                            <strong>{{ $message->sender?->name ?? 'System' }}</strong>
                            <small class="text-muted">
                                {{ $message->created_at->format('M j, H:i') }}
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
@endsection
