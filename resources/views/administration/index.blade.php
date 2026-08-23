@extends('layouts.app')
@section('title','Administration')
@section('content')
@php
    $profileName = $activeBusiness?->name ?? 'Active Profile';
    $roleById = $roles->keyBy('id');
    $departmentById = $departments->keyBy('id');
    $branchById = $branches->keyBy('id');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Administration</h1>
        <p class="text-muted mb-0">{{ $profileName }} profile administration, users, departments and feature access.</p>
    </div>
    <a class="btn btn-warning" href="{{ route('administration.users.create') }}">Create User</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="row g-3 mb-4">
    @foreach([['Users',$users->count()],['Active',$users->where('status','Active')->count()],['Pending',$users->where('status','Pending Invitation')->count()],['Roles',$roles->count()],['Departments',$departments->count()],['Branches',$branches->count()]] as [$label,$count])
        <div class="col-md-2">
            <div class="card p-3">
                <small>{{ $label }}</small>
                <strong class="fs-4">{{ $count }}</strong>
            </div>
        </div>
    @endforeach
</div>

<div class="card p-3 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-lg-7">
            <small class="text-uppercase text-muted fw-semibold">Current Profile</small>
            <h2 class="h4 mb-1">{{ $profileName }}</h2>
            <p class="text-muted mb-0">Administration changes made here apply only to this profile/business.</p>
        </div>
        <div class="col-lg-5">
            <div class="row g-2" title="Adding more profiles is coming soon. Each profile manages one business for now.">
                <div class="col">
                    <label class="form-label">Add another profile</label>
                    <input class="form-control" value="Coming soon" disabled aria-label="Add profile coming soon">
                </div>
                <div class="col-auto align-self-end">
                    <button class="btn btn-outline-secondary" type="button" disabled>Coming Soon</button>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    @foreach(['access'=>'Profile Access','users'=>'Users','roles'=>'Roles & Permissions','structure'=>'Departments & Teams','approvals'=>'Approvals','activity'=>'Login & Devices','security'=>'Security','audit'=>'Audit'] as $id=>$label)
        <li class="nav-item">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#a-{{ $id }}">{{ $label }}</button>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="a-access">
        <div class="row g-3">
            <div class="col-xl-5">
                <div class="card p-3">
                    <h3 class="h5">Assign Profile Access</h3>
                    <p class="text-muted">Add an existing account or invite a new person by email.</p>
                    <form method="post" action="{{ route('administration.access.assign') }}" class="row g-2">
                        @csrf
                        <div class="col-md-6"><input class="form-control" name="name" placeholder="Full name"></div>
                        <div class="col-md-6"><input class="form-control" type="email" name="email" placeholder="Email" required></div>
                        <div class="col-md-6"><input class="form-control" name="username" placeholder="Username, optional"></div>
                        <div class="col-md-6">
                            <select class="form-select" name="iam_role_id">
                                <option value="">Default role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" name="department_id">
                                <option value="">Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" name="branch_id">
                                <option value="">Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12"><input class="form-control" name="approval_level" placeholder="Approval level, optional"></div>
                        <div class="col-12">
                            <button class="btn btn-warning w-100">Save Access & Invite</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card p-3">
                    <h3 class="h5">Feature Permissions</h3>
                    <p class="text-muted">Tick permissions here to create an email-specific access role for this profile.</p>
                    <form method="post" action="{{ route('administration.access.assign') }}">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-md-6"><input class="form-control" name="name" placeholder="Full name"></div>
                            <div class="col-md-6"><input class="form-control" type="email" name="email" placeholder="Email" required></div>
                            <div class="col-md-4">
                                <select class="form-select" name="department_id">
                                    <option value="">Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="branch_id">
                                    <option value="">Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><input class="form-control" name="approval_level" placeholder="Approval level"></div>
                        </div>
                        <div class="permissions-panel">
                            @foreach($permissions as $module=>$items)
                                <div class="border rounded p-3 mb-2">
                                    <strong class="d-block mb-2">{{ ucfirst($module) }}</strong>
                                    <div class="row">
                                        @foreach($items as $permission)
                                            <label class="col-md-6 col-xl-4 mb-2">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"> {{ $permission->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-warning mt-2">Save Custom Feature Access</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="a-users">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card p-3">
                    <h3 class="h6">Invite User</h3>
                    <form method="post" action="{{ route('administration.users.store') }}" class="row g-2">
                        @csrf
                        <input class="form-control" name="name" placeholder="Full name" required>
                        <input class="form-control" name="email" type="email" placeholder="Email" required>
                        <input class="form-control" name="username" placeholder="Username, optional">
                        <input class="form-control" name="job_title" placeholder="Job title">
                        <select class="form-select" name="iam_role_id">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
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
                        <select class="form-select" name="manager_id">
                            <option value="">Manager</option>
                            @foreach($users as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-warning">Send Invitation</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card p-3">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>User</th><th>Profile Access</th><th>Status</th><th>Devices</th><th>Controls</th></tr></thead>
                            <tbody>
                            @foreach($users as $user)
                                @php($membership = $memberships->get($user->id))
                                <tr>
                                    <td>
                                        {{ $user->name }}
                                        <small class="d-block">{{ $user->email }} · {{ $user->job_title ?: 'Team member' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $roleById->get($membership?->iam_role_id)?->name ?? 'No role' }}</strong>
                                        <small class="d-block text-muted">
                                            {{ $departmentById->get($membership?->department_id)?->name ?? 'No department' }}
                                            ·
                                            {{ $branchById->get($membership?->branch_id)?->name ?? 'No branch' }}
                                        </small>
                                    </td>
                                    <td>{{ $membership?->status ?? $user->status }}</td>
                                    <td>{{ $user->devices->whereNull('revoked_at')->count() }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <form method="post" action="{{ route('administration.users.unlock',$user) }}">@csrf<button class="btn btn-sm btn-outline-success">Unlock</button></form>
                                            <form method="post" action="{{ route('administration.users.force-reset',$user) }}">@csrf<button class="btn btn-sm btn-outline-warning">Reset</button></form>
                                            <form method="post" action="{{ route('administration.users.status',$user) }}">@csrf<input type="hidden" name="status" value="Suspended"><button class="btn btn-sm btn-outline-danger">Suspend</button></form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="a-roles">
        <div class="card p-3">
            <form method="post" action="{{ route('administration.roles.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4"><input class="form-control" name="name" placeholder="Custom role name" required></div>
                    <div class="col-md-4"><input class="form-control" name="slug" placeholder="role-slug" required></div>
                    <div class="col-md-4"><input class="form-control" name="landing_route" value="dashboard" required></div>
                </div>
                @foreach($permissions as $module=>$items)
                    <div class="mt-3">
                        <strong>{{ ucfirst($module) }}</strong>
                        <div class="row mt-2">
                            @foreach($items as $permission)
                                <label class="col-md-4 mb-2"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}"> {{ $permission->name }}</label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <button class="btn btn-warning mt-3">Create Role</button>
            </form>
        </div>
    </div>

    <div class="tab-pane fade" id="a-structure">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card p-3">
                    <h3 class="h6">Departments</h3>
                    <form method="post" action="{{ route('administration.departments.store') }}">
                        @csrf
                        <input class="form-control mb-2" name="name" placeholder="Department name" required>
                        <input class="form-control mb-2" name="code" placeholder="Code" required>
                        <select class="form-select mb-2" name="manager_id">
                            <option value="">Manager</option>
                            @foreach($users as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
                        <button class="btn btn-warning w-100">Add Department</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card p-3">
                    <h3 class="h6">Branches</h3>
                    <form method="post" action="{{ route('administration.branches.store') }}">
                        @csrf
                        <input class="form-control mb-2" name="name" placeholder="Branch name" required>
                        <input class="form-control mb-2" name="code" placeholder="Code" required>
                        <textarea class="form-control mb-2" name="address" placeholder="Address"></textarea>
                        <button class="btn btn-warning w-100">Add Branch</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card p-3">
                    <h3 class="h6">Teams</h3>
                    <form method="post" action="{{ route('administration.teams.store') }}">
                        @csrf
                        <input class="form-control mb-2" name="name" placeholder="Team name" required>
                        <select class="form-select mb-2" name="type">
                            <option>Project</option><option>Sales</option><option>Support</option><option>Technical</option>
                        </select>
                        <select class="form-select mb-2" name="manager_id">
                            <option value="">Manager</option>
                            @foreach($users as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-warning w-100">Add Team</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card p-3 mt-3">
            <div class="row g-3">
                <div class="col-md-4"><h4 class="h6">Departments</h4>@forelse($departments as $department)<div class="border rounded p-2 mb-2">{{ $department->name }}<small class="d-block text-muted">{{ $department->code }}</small></div>@empty<p class="text-muted">No departments yet.</p>@endforelse</div>
                <div class="col-md-4"><h4 class="h6">Branches</h4>@forelse($branches as $branch)<div class="border rounded p-2 mb-2">{{ $branch->name }}<small class="d-block text-muted">{{ $branch->code }}</small></div>@empty<p class="text-muted">No branches yet.</p>@endforelse</div>
                <div class="col-md-4"><h4 class="h6">Teams</h4>@forelse($teams as $team)<div class="border rounded p-2 mb-2">{{ $team->name }}<small class="d-block text-muted">{{ $team->type }}</small></div>@empty<p class="text-muted">No teams yet.</p>@endforelse</div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="a-approvals">
        <div class="card p-3">
            <form method="post" action="{{ route('administration.workflows.store') }}">
                @csrf
                <input class="form-control mb-2" name="name" placeholder="Workflow name" required>
                <select class="form-select mb-2" name="document_type"><option>Supplier Bill</option><option>Purchase Order</option><option>Letter</option></select>
                @foreach([1,2,3] as $n)
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input class="form-control" name="step_names[]" placeholder="Approval step {{ $n }}" {{ $n === 1 ? 'required' : '' }}></div>
                        <div class="col-6"><select class="form-select" name="role_ids[]"><option value="">Any approver</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div>
                    </div>
                @endforeach
                <button class="btn btn-warning">Create Workflow</button>
            </form>
        </div>
    </div>

    <div class="tab-pane fade" id="a-activity">
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>When</th><th>User</th><th>Event</th><th>IP / Device</th><th>Result</th></tr></thead>
                    <tbody>@foreach($activities as $activity)<tr><td>{{ $activity->created_at }}</td><td>{{ $activity->email }}</td><td>{{ $activity->event }}</td><td>{{ $activity->ip_address }} · {{ $activity->device }} / {{ $activity->browser }}</td><td>{{ $activity->successful ? 'Success' : 'Failed' }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="a-security">
        <div class="card p-3 mb-3">
            <form method="post" action="{{ route('administration.security.update') }}" class="row g-3">
                @csrf @method('PUT')
                @foreach(['max_failed_attempts','lockout_minutes','session_timeout_minutes','invitation_expiry_hours','password_expiry_days','password_history_count'] as $field)
                    <div class="col-md-4"><label>{{ $field }}</label><input class="form-control" type="number" name="{{ $field }}" value="{{ $settings->$field }}"></div>
                @endforeach
                <div class="col-12"><button class="btn btn-warning">Save Policy</button></div>
            </form>
        </div>
        <div class="card p-3">
            <h3 class="h5">Outgoing Email (SMTP)</h3>
            <p class="text-muted">Credentials are encrypted. The saved password is never displayed. Database credentials cannot be changed here.</p>
            <form method="post" action="{{ route('administration.mail.update') }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-12"><label><input type="checkbox" name="enabled" value="1" @checked($mailSetting?->enabled)> Use these settings</label></div>
                <div class="col-md-6"><label class="form-label">SMTP host</label><input class="form-control" name="host" value="{{ old('host',$mailSetting?->host ?? config('mail.mailers.smtp.host')) }}" required></div>
                <div class="col-md-3"><label class="form-label">Port</label><input class="form-control" type="number" name="port" value="{{ old('port',$mailSetting?->port ?? config('mail.mailers.smtp.port', 465)) }}" required></div>
                <div class="col-md-3"><label class="form-label">Encryption</label><select class="form-select" name="scheme"><option value="ssl" @selected(($mailSetting?->scheme ?? 'ssl') === 'ssl')>SSL/TLS</option><option value="tls" @selected($mailSetting?->scheme === 'tls')>STARTTLS</option></select></div>
                <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="username" value="{{ old('username',$mailSetting?->username ?? config('mail.mailers.smtp.username')) }}" autocomplete="off"></div>
                <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="password" placeholder="{{ $mailSetting?->password ? 'Saved - leave blank to keep' : 'Enter SMTP password' }}" autocomplete="new-password"></div>
                <div class="col-md-6"><label class="form-label">From address</label><input class="form-control" type="email" name="from_address" value="{{ old('from_address',$mailSetting?->from_address ?? config('mail.from.address')) }}" required></div>
                <div class="col-md-6"><label class="form-label">From name</label><input class="form-control" name="from_name" value="{{ old('from_name',$mailSetting?->from_name ?? config('app.name')) }}" required></div>
                <div class="col-12"><button class="btn btn-warning">Save Encrypted Settings</button></div>
            </form>
            @if($mailSetting)
                <hr>
                <form method="post" action="{{ route('administration.mail.test') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8"><label class="form-label">Send test to</label><input class="form-control" type="email" name="test_email" value="{{ auth()->user()->email }}" required></div>
                    <div class="col-md-4"><button class="btn btn-outline-warning w-100">Send Test Email</button></div>
                </form>
            @endif
        </div>
    </div>

    <div class="tab-pane fade" id="a-audit">
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>When</th><th>Event</th><th>User</th><th>IP</th></tr></thead>
                    <tbody>@foreach($auditLogs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->event }}</td><td>#{{ $log->user_id }}</td><td>{{ $log->ip_address }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 mt-3">
    <h3 class="h5">Invitation Management</h3>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>User</th><th>Status</th><th>Expires</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($invitations as $invite)
                @php($inviteStatus = $invite->status === 'Pending' && $invite->expires_at->isPast() ? 'Expired' : $invite->status)
                <tr>
                    <td>{{ $invite->user?->name }}<small class="d-block">{{ $invite->user?->email }}</small></td>
                    <td>{{ $inviteStatus }}</td>
                    <td>{{ $invite->expires_at->format('d M Y H:i') }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @if($inviteStatus === 'Pending')
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ route('public.administration.activate',$invite->token) }}')">Copy Link</button>
                                <form method="post" action="{{ route('administration.invitations.cancel',$invite) }}">@csrf<button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                            @endif
                            @if(in_array($inviteStatus,['Expired','Cancelled']))
                                <form method="post" action="{{ route('administration.invitations.renew',$invite) }}">@csrf<button class="btn btn-sm btn-outline-warning">Generate New Link</button></form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No invitations yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(session('recovery_link'))
    <div class="alert alert-warning mt-3">
        <strong>One-time recovery link</strong>
        <div class="input-group mt-2">
            <input class="form-control" value="{{ session('recovery_link') }}" readonly id="recovery-link">
            <button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText(document.querySelector('#recovery-link').value)">Copy</button>
        </div>
        <small>Share securely. It expires in 30 minutes and is shown only now.</small>
    </div>
@endif

<div class="card p-3 mt-3">
    <h3 class="h5">Emergency Access</h3>
    <p class="text-muted">Administrators never see passwords. These actions are audited.</p>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>User</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}<small class="d-block">{{ $user->status }}</small></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <form method="post" action="{{ route('administration.users.force-reset',$user) }}">@csrf<button class="btn btn-sm btn-outline-warning">Force Reset</button></form>
                            <form method="post" action="{{ route('administration.users.unlock',$user) }}">@csrf<button class="btn btn-sm btn-outline-success">Unlock</button></form>
                            <form method="post" action="{{ route('administration.users.logout-sessions',$user) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Log Out Sessions</button></form>
                            <form method="post" action="{{ route('administration.users.recovery-link',$user) }}">@csrf<button class="btn btn-sm btn-outline-danger">Recovery Link</button></form>
                            @if($user->status === 'Pending Invitation')
                                <form method="post" action="{{ route('administration.users.resend',$user) }}">@csrf<button class="btn btn-sm btn-outline-primary">New Invitation</button></form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card p-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="h5 mb-1">Team presence</h3>
            <p class="text-muted mb-0">Recent session activity for this profile.</p>
        </div>
        <span class="status-pill">{{ $users->filter(fn($user) => (($presence[$user->id] ?? 0) >= now()->subMinutes(5)->timestamp))->count() }} online</span>
    </div>
    <div class="row g-2">
        @foreach($users as $user)
            @php($isOnline = (($presence[$user->id] ?? 0) >= now()->subMinutes(5)->timestamp))
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('administration.users.activity',$user) }}" class="d-flex align-items-center gap-3 border rounded-3 p-3 text-decoration-none h-100">
                    <span class="presence-dot {{ $isOnline ? 'online' : 'offline' }}" aria-label="{{ $isOnline ? 'Online' : 'Offline' }}"></span>
                    <div class="flex-grow-1"><strong class="d-block text-body">{{ $user->name }}</strong><small class="text-muted">{{ $user->job_title ?: 'Team member' }}</small></div>
                    <small class="{{ $isOnline ? 'text-success' : 'text-danger' }} fw-semibold">{{ $isOnline ? 'Online' : 'Offline' }}</small>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>

@php($deletableUsers = $users->whereIn('status',['Pending Invitation','Suspended']))
@if($deletableUsers->isNotEmpty())
    <div class="card p-3 mt-3">
        <h3 class="h6">Delete Setup Users</h3>
        <p class="text-muted">Pending invitation and suspended accounts can be permanently deleted. Accounts linked to protected records will be retained.</p>
        <div class="d-flex flex-wrap gap-2">
            @foreach($deletableUsers as $user)
                <form method="post" action="{{ route('administration.users.destroy',$user) }}" onsubmit="return confirm('Permanently delete this user and associated access records? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="confirm_delete" value="1">
                    <button class="btn btn-sm btn-outline-danger">Delete {{ $user->name }}</button>
                </form>
            @endforeach
        </div>
    </div>
@endif

<style>
    .presence-dot{width:10px;height:10px;border-radius:50%;flex:0 0 10px}
    .presence-dot.online{background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.13)}
    .presence-dot.offline{background:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.1)}
    .permissions-panel{max-height:420px;overflow:auto}
</style>
@endsection
