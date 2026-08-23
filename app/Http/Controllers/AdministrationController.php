<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Branch;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\IamPermission;
use App\Models\IamRole;
use App\Models\MailSetting;
use App\Models\SecuritySetting;
use App\Models\Team;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserInvitation;
use App\Services\IamService;
use App\Services\ModuleRegistry;
use App\Services\OutgoingMailService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdministrationController extends Controller
{
    private ?Collection $profilePermissionsCache = null;

    private ?array $profilePermissionIdsCache = null;

    private const SHARED_PERMISSION_NAMES = [
        'administration.view', 'users.view', 'users.create', 'users.edit', 'users.deactivate',
        'roles.manage', 'permissions.manage', 'branches.manage', 'teams.manage', 'approvals.manage',
        'security.manage', 'audit.view', 'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
        'projects.view', 'projects.create', 'projects.edit', 'projects.close', 'finance.view',
        'finance.coa.manage', 'finance.gl.view', 'finance.gl.post', 'finance.gl.reverse', 'finance.gl.unreverse',
        'finance.ar.view', 'finance.ar.manage', 'finance.ap.view', 'finance.ap.approve', 'finance.ap.manage',
        'finance.banking.manage', 'finance.reconciliation.manage', 'finance.assets.manage',
        'finance.periods.manage', 'finance.reports.view', 'letters.view', 'letters.create', 'letters.edit', 'letters.delete',
        'inventory.view', 'inventory.adjust', 'reports.view', 'reports.export',
        'etims.view', 'etims.manage', 'etims.reports', 'etims.retry',
        'communication.view', 'communication.send', 'communication.create_group', 'communication.manage_group',
        'communication.create_channel', 'communication.manage_channel', 'communication.upload', 'communication.delete_own',
        'communication.moderate', 'communication.announcements.create', 'communication.announcements.manage',
        'communication.mass_mention', 'communication.audit', 'communication.settings',
        'communication.manage', 'communication.admin', 'communication.announce', 'communication.reports',
    ];

    public function __construct(private IamService $iam, private OutgoingMailService $outgoingMail, private ModuleRegistry $modules) {}

    public function index()
    {
        $this->iam->bootstrap();

        $users = $this->profileUsers()->with('manager', 'devices', 'teams')->orderBy('name')->get();
        $userIds = $users->pluck('id');
        $mailSettingsReady = Schema::hasTable('mail_settings');
        $companySetting = Schema::hasTable('company_settings')
            ? CompanySetting::where('business_id', $this->businessId())->first()
            : null;
        $presence = DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        return view('administration.index', [
            'users' => $users,
            'presence' => $presence,
            'memberships' => DB::table('business_user')->where('business_id', $this->businessId())->get()->keyBy('user_id'),
            'roles' => IamRole::where('business_id', $this->businessId())->with('permissions')->orderBy('name')->get(),
            'permissions' => $this->profilePermissions(),
            'permissionScope' => $this->permissionScopeLabel(),
            'departments' => Department::where('business_id', $this->businessId())->orderBy('name')->get(),
            'branches' => Branch::where('business_id', $this->businessId())->orderBy('name')->get(),
            'teams' => Team::with('users', 'manager')->where('business_id', $this->businessId())->orderBy('name')->get(),
            'invitations' => UserInvitation::with('user')->where('business_id', $this->businessId())->latest()->get(),
            'activities' => DB::table('login_activities')->whereIn('user_id', $userIds)->latest()->limit(100)->get(),
            'auditLogs' => AdminAuditLog::where('business_id', $this->businessId())->latest()->limit(100)->get(),
            'settings' => SecuritySetting::firstOrCreate(['business_id' => $this->businessId()]),
            'companySetting' => $companySetting,
            'mailSetting' => $mailSettingsReady ? MailSetting::where('business_id', $this->businessId())->first() : null,
            'mailSettingsReady' => $mailSettingsReady,
            'workflows' => DB::table('approval_workflows')->where('business_id', $this->businessId())->get(),
            'activeBusiness' => ActiveBusiness::current(),
        ]);
    }

    public function userActivity(User $user)
    {
        abort_if($user->role === 'client_portal', 404);
        $this->assertProfileUser($user);

        $lastActivity = DB::table('sessions')->where('user_id', $user->id)->max('last_activity');
        $online = $lastActivity && $lastActivity >= now()->subMinutes(5)->timestamp;
        $audit = AdminAuditLog::where('business_id', $this->businessId())
            ->where('user_id', $user->id)
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn ($log) => (object) ['event' => $log->event, 'when' => $log->created_at]);
        $auth = DB::table('login_activities')
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->whereIn('event', ['login', 'logout'])
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($log) => (object) ['event' => 'auth:'.$log->event, 'when' => Carbon::parse($log->created_at)]);
        $timeline = $audit->concat($auth)->sortByDesc('when')->take(30)->values();

        return view('administration.user-activity', compact('user', 'online', 'lastActivity', 'timeline'));
    }

    public function createUser()
    {
        $this->iam->bootstrap();

        return view('administration.user-wizard', [
            'users' => $this->profileUsers()->with('teams', 'directPermissions')->orderBy('name')->get(),
            'roles' => IamRole::where('business_id', $this->businessId())->orderBy('name')->get(),
            'permissions' => $this->profilePermissions(),
            'permissionScope' => $this->permissionScopeLabel(),
            'departments' => Department::where('business_id', $this->businessId())->orderBy('name')->get(),
            'branches' => Branch::where('business_id', $this->businessId())->orderBy('name')->get(),
            'teams' => Team::where('business_id', $this->businessId())->orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $this->validateUserPayload($request);
        $user = $this->createOrAttachUser($data);

        $sent = $user->status === 'Pending Invitation' ? $this->sendInvitation($user) : false;
        $this->iam->audit('user.created', $user);

        return redirect()
            ->route('administration.index')
            ->with($sent ? 'status' : 'status', $sent ? 'User created and invitation sent.' : 'User access saved for this profile.');
    }

    public function assignAccess(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'iam_role_id' => ['nullable', Rule::exists('iam_roles', 'id')->where('business_id', $this->businessId())],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', $this->businessId())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', $this->businessId())],
            'approval_level' => ['nullable', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:iam_permissions,id'],
        ]);

        $data['permissions'] = $this->validatedProfilePermissionIds($data['permissions'] ?? []);
        $user = $this->createOrAttachUser($data + ['setup_mode' => 'default']);
        if (! empty($data['permissions'])) {
            $role = $this->roleForEmailPermissions($data['email'], $data['permissions']);
            DB::table('business_user')
                ->where('business_id', $this->businessId())
                ->where('user_id', $user->id)
                ->update(['iam_role_id' => $role->id, 'updated_at' => now()]);
        }

        $sent = $user->status === 'Pending Invitation' ? $this->sendInvitation($user) : false;
        $this->iam->audit('profile.access.assigned', $user);

        return back()->with($sent ? 'status' : 'status', $sent ? 'Profile access saved and invitation sent.' : 'Profile access saved.');
    }

    public function updateUser(Request $request, User $user)
    {
        $this->assertProfileUser($user);
        $old = $user->getAttributes();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_number' => ['nullable', 'max:100', Rule::unique('users')->ignore($user)],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->whereIn('id', $this->profileUserIds())],
            'iam_role_id' => ['required', Rule::exists('iam_roles', 'id')->where('business_id', $this->businessId())],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', $this->businessId())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', $this->businessId())],
        ]);

        $membership = collect($data)->only(['iam_role_id', 'department_id', 'branch_id'])->all();
        $user->update(collect($data)->except(['iam_role_id', 'department_id', 'branch_id'])->all());
        DB::table('business_user')
            ->where(['business_id' => $this->businessId(), 'user_id' => $user->id])
            ->update($membership + ['updated_at' => now()]);

        $this->iam->audit('user.updated', $user, $old);

        return back()->with('status', 'User updated.');
    }

    public function status(Request $request, User $user)
    {
        $this->assertProfileUser($user);
        $data = $request->validate(['status' => ['required', 'in:Active,Inactive,Locked,Suspended,Archived']]);
        $user->update([
            'status' => $data['status'],
            'is_active' => $data['status'] === 'Active',
            'locked_at' => $data['status'] === 'Locked' ? now()->addYears(10) : null,
            'session_version' => ($user->session_version ?? 0) + 1,
        ]);
        DB::table('business_user')
            ->where(['business_id' => $this->businessId(), 'user_id' => $user->id])
            ->update(['status' => $data['status'], 'updated_at' => now()]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $this->iam->audit('user.status.'.$data['status'], $user);

        return back()->with('status', 'User status updated and sessions revoked.');
    }

    public function unlock(User $user)
    {
        $this->assertProfileUser($user);
        $user->update(['status' => 'Active', 'is_active' => true, 'locked_at' => null, 'failed_login_attempts' => 0]);
        DB::table('business_user')
            ->where(['business_id' => $this->businessId(), 'user_id' => $user->id])
            ->update(['status' => 'Active', 'updated_at' => now()]);
        $this->iam->audit('user.unlocked', $user);

        return back()->with('status', 'User unlocked.');
    }

    public function forceReset(User $user)
    {
        $this->assertProfileUser($user);
        $user->update(['force_password_change' => true, 'session_version' => ($user->session_version ?? 0) + 1]);
        DB::table('sessions')->where('user_id', $user->id)->delete();

        try {
            $this->outgoingMail->apply($this->businessId());
            $result = PasswordBroker::sendResetLink(['email' => $user->email]);
            $sent = $result === PasswordBroker::RESET_LINK_SENT;
        } catch (\Throwable $e) {
            report($e);
            $sent = false;
        }

        $this->iam->audit($sent ? 'password.reset.forced' : 'password.reset.delivery_failed', $user);

        return back()->with($sent ? 'status' : 'warning', $sent ? 'Password reset link sent; all sessions expired.' : 'Sessions were expired and password change was required, but the reset email could not be delivered. Check the email address and SMTP settings.');
    }

    public function resend(User $user)
    {
        $this->assertProfileUser($user);
        $sent = $this->sendInvitation($user);

        return back()->with($sent ? 'status' : 'warning', $sent ? 'A new activation invitation was sent.' : 'A new invitation was created, but email delivery failed. Check the SMTP credentials and try again.');
    }

    public function destroyUser(Request $request, User $user)
    {
        $this->assertProfileUser($user);
        $request->validate(['confirm_delete' => ['accepted']]);
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');

        if (! in_array($user->status, ['Pending Invitation', 'Suspended'], true)) {
            return back()->with('warning', 'Only pending invitation or suspended accounts can be permanently deleted. Active users must be suspended first.');
        }

        try {
            DB::transaction(function () use ($user) {
                $this->iam->audit('user.deleted', $user);
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $this->detachFromCurrentProfile($user);
                $hasOtherProfiles = DB::table('business_user')->where('user_id', $user->id)->exists()
                    || DB::table('tenant_user')->where('user_id', $user->id)->exists();
                if (! $hasOtherProfiles) {
                    $user->delete();
                }
            });

            return back()->with('status', 'User access removed from this profile.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('warning', 'This user is linked to records that must be preserved and cannot be deleted.');
        }
    }

    public function cancelInvitation(UserInvitation $invitation)
    {
        abort_unless((int) $invitation->business_id === $this->businessId(), 404);
        $invitation->update(['status' => 'Cancelled', 'cancelled_at' => now()]);
        $this->iam->audit('invitation.cancelled', $invitation);

        return back()->with('status', 'Invitation cancelled.');
    }

    public function renewInvitation(UserInvitation $invitation)
    {
        abort_unless((int) $invitation->business_id === $this->businessId(), 404);
        $sent = $this->sendInvitation($invitation->user);

        return back()->with($sent ? 'status' : 'warning', $sent ? 'A new invitation link was generated and sent.' : 'A new link was generated, but email delivery failed. Check the SMTP credentials and try again.');
    }

    public function logoutSessions(User $user)
    {
        $this->assertProfileUser($user);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->increment('session_version');
        $this->iam->audit('sessions.revoked', $user);

        return back()->with('status', 'All sessions for '.$user->name.' were logged out.');
    }

    public function recoveryLink(User $user)
    {
        $this->assertProfileUser($user);
        $token = Str::random(64);
        DB::table('admin_recovery_links')->where('user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()]);
        DB::table('admin_recovery_links')->insert([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->iam->audit('recovery.link.created', $user);

        return back()->with('status', 'One-time recovery link generated. It expires in 30 minutes.')->with('recovery_link', route('administration.recovery', $token));
    }

    public function recoveryForm(string $token)
    {
        $record = DB::table('admin_recovery_links')->where('token', hash('sha256', $token))->whereNull('used_at')->where('expires_at', '>', now())->first();
        abort_unless($record, 404);

        return view('administration.recovery', ['token' => $token, 'user' => User::findOrFail($record->user_id)]);
    }

    public function recover(Request $request, string $token)
    {
        $record = DB::table('admin_recovery_links')->where('token', hash('sha256', $token))->whereNull('used_at')->where('expires_at', '>', now())->first();
        abort_unless($record, 404);
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()]]);
        $user = User::findOrFail($record->user_id);
        DB::transaction(function () use ($record, $user, $data) {
            $user->update(['password' => $data['password'], 'force_password_change' => false, 'password_changed_at' => now(), 'session_version' => ($user->session_version ?? 0) + 1]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('admin_recovery_links')->where('id', $record->id)->update(['used_at' => now(), 'updated_at' => now()]);
        });

        return redirect()->route('login')->with('status', 'Password recovered. You may now sign in.');
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('iam_roles', 'slug')->where('business_id', $this->businessId())],
            'landing_route' => ['required', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:iam_permissions,id'],
        ]);
        $data['permissions'] = $this->validatedProfilePermissionIds($data['permissions'] ?? []);
        $role = IamRole::create(collect($data)->except('permissions')->all() + ['business_id' => $this->businessId()]);
        $role->permissions()->sync($data['permissions'] ?? []);
        $this->iam->audit('role.created', $role);

        return back()->with('status', 'Custom role created.');
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->where('business_id', $this->businessId())],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->whereIn('id', $this->profileUserIds())],
            'description' => ['nullable', 'string'],
        ]);

        $department = Department::create($data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        $this->iam->audit('department.created', $department);

        return back()->with('status', 'Department created.');
    }

    public function storeBranch(Request $request)
    {
        $branch = Branch::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('branches', 'code')->where('business_id', $this->businessId())],
            'address' => ['nullable', 'string'],
        ]));
        $this->iam->audit('branch.created', $branch);

        return back()->with('status', 'Branch created.');
    }

    public function storeTeam(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->whereIn('id', $this->profileUserIds())],
            'users' => ['nullable', 'array'],
            'users.*' => [Rule::exists('users', 'id')->whereIn('id', $this->profileUserIds())],
        ]);
        $team = Team::create(collect($data)->except('users')->all());
        $team->users()->sync($data['users'] ?? []);
        $this->iam->audit('team.created', $team);

        return back()->with('status', 'Team created.');
    }

    public function storeWorkflow(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:100'],
            'step_names' => ['required', 'array', 'min:1'],
            'step_names.*' => ['required', 'string', 'max:255'],
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['nullable', Rule::exists('iam_roles', 'id')->where('business_id', $this->businessId())],
        ]);
        $id = DB::table('approval_workflows')->insertGetId(['business_id' => $this->businessId(), 'name' => $data['name'], 'document_type' => $data['document_type'], 'created_at' => now(), 'updated_at' => now()]);
        foreach ($data['step_names'] as $i => $name) {
            DB::table('approval_workflow_steps')->insert(['approval_workflow_id' => $id, 'iam_role_id' => $data['role_ids'][$i] ?? null, 'step_order' => $i + 1, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        return back()->with('status', 'Approval workflow created.');
    }

    public function security(Request $request)
    {
        $data = $request->validate([
            'max_failed_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'lockout_minutes' => ['required', 'integer', 'min:1'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5'],
            'invitation_expiry_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'password_expiry_days' => ['nullable', 'integer', 'min:1'],
            'password_history_count' => ['required', 'integer', 'min:0', 'max:20'],
        ]);
        SecuritySetting::updateOrCreate(['business_id' => $this->businessId()], $data);
        $this->iam->audit('security.settings.updated');

        return back()->with('status', 'Security policy updated.');
    }

    public function updateMail(Request $request)
    {
        $setting = MailSetting::firstOrNew(['business_id' => $this->businessId()]);
        $useOwnSmtp = $request->boolean('use_own_smtp');

        $rules = [
            'enabled' => ['nullable', 'boolean'],
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string', 'max:255'],
            'use_own_smtp' => ['nullable', 'boolean'],
        ];

        if ($useOwnSmtp) {
            $rules += [
                'host' => ['required', 'string', 'max:255'],
                'port' => ['required', 'integer', 'min:1', 'max:65535'],
                'scheme' => ['nullable', 'in:tls,ssl,smtp,smtps'],
                'username' => ['required', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:1000'],
            ];
        }

        $data = $request->validate($rules);

        if ($useOwnSmtp && blank($data['password'] ?? null) && blank($setting->password)) {
            return back()->withErrors(['password' => 'Enter the corporate mailbox password or app password once to connect this profile.'])->withInput();
        }

        if ($useOwnSmtp) {
            $data['scheme'] = match ($data['scheme'] ?? null) {
                'smtps' => 'ssl',
                'smtp' => 'tls',
                default => $data['scheme'] ?? ((int) $data['port'] === 465 ? 'ssl' : 'tls'),
            };

            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }
        } else {
            $smtp = config('mail.mailers.smtp', []);
            $port = (int) ($smtp['port'] ?? 587);
            $data['host'] = $smtp['host'] ?? '127.0.0.1';
            $data['port'] = $port;
            $data['username'] = null;
            $data['password'] = null;
            $data['scheme'] = match ($smtp['scheme'] ?? null) {
                'smtps' => 'ssl',
                'smtp' => 'tls',
                default => $smtp['scheme'] ?? ($port === 465 ? 'ssl' : 'tls'),
            };
        }

        $setting->fill($data + ['enabled' => $request->boolean('enabled')])->save();
        $this->iam->audit('mail.settings.updated', $setting);

        return back()->with('status', 'Mail settings saved securely.');
    }

    public function testMail(Request $request)
    {
        $data = $request->validate(['test_email' => ['required', 'email']]);
        $setting = MailSetting::where('business_id', $this->businessId())->firstOrFail();

        try {
            $setting->apply();
            $this->outgoingMail->sendRaw($data['test_email'], 'SMTP configuration test', 'Your '.$this->profileName().' SMTP settings are working.', businessId: $this->businessId());
            $this->iam->audit('mail.settings.test_succeeded', $setting);

            return back()->with('status', 'Test email sent successfully.');
        } catch (\Throwable $e) {
            report($e);
            $this->iam->audit('mail.settings.test_failed', $setting);

            return back()->with('warning', 'Test failed: '.$this->outgoingMail->userFacingError($e));
        }
    }

    public function revokeDevice(UserDevice $device)
    {
        $this->assertProfileUser($device->user);
        $device->update(['revoked_at' => now()]);
        DB::table('sessions')->where('user_id', $device->user_id)->delete();

        return back()->with('status', 'Device access revoked.');
    }

    public function activateForm(string $token)
    {
        $invitation = UserInvitation::where('token', $token)->where('status', 'Pending')->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', '>', now())->firstOrFail();

        $activationAction = route('administration.activate.store', $invitation->token);

        return view('administration.activate', compact('activationAction', 'invitation'));
    }

    public function activate(Request $request, string $token)
    {
        $invitation = UserInvitation::where('token', $token)->where('status', 'Pending')->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', '>', now())->firstOrFail();
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()], 'terms' => ['accepted']]);
        $invitation->user->update(['password' => $data['password'], 'status' => 'Active', 'is_active' => true, 'email_verified_at' => now(), 'password_changed_at' => now()]);
        $invitation->update(['accepted_at' => now(), 'status' => 'Accepted']);
        DB::table('business_user')->where(['business_id' => $invitation->business_id, 'user_id' => $invitation->user_id])->update(['status' => 'Active', 'updated_at' => now()]);

        return redirect()->route('login')->with('status', 'Account activated. You can now sign in.');
    }

    private function validateUserPayload(Request $request): array
    {
        $profileUserIds = $this->profileUserIds();

        $data = $request->validate([
            'setup_mode' => ['nullable', 'in:default,clone,custom'],
            'clone_user_id' => ['nullable', 'required_if:setup_mode,clone', Rule::exists('users', 'id')->whereIn('id', $profileUserIds)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'iam_role_id' => ['nullable', Rule::exists('iam_roles', 'id')->where('business_id', $this->businessId())],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('business_id', $this->businessId())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', $this->businessId())],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->whereIn('id', $profileUserIds)],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => [Rule::exists('teams', 'id')->where('business_id', $this->businessId())],
            'approval_level' => ['nullable', 'string', 'max:100'],
            'custom_role_name' => ['nullable', 'required_if:setup_mode,custom', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:iam_permissions,id'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $existing = User::where('email', $data['email'])->first();
        if (! $existing && ! empty($data['username']) && User::where('username', $data['username'])->exists()) {
            abort(422, 'That username is already used by another account.');
        }

        $data['permissions'] = $this->validatedProfilePermissionIds($data['permissions'] ?? []);

        return $data;
    }

    private function createOrAttachUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $mode = $data['setup_mode'] ?? 'default';
            if (blank($data['name'] ?? null)) {
                $data['name'] = Str::of($data['email'])->before('@')->replace(['.', '_', '-'], ' ')->title()->value();
            }

            if ($mode === 'clone') {
                $source = User::with('teams')->findOrFail($data['clone_user_id']);
                $sourceMember = DB::table('business_user')->where(['business_id' => $this->businessId(), 'user_id' => $source->id])->first();
                foreach (['iam_role_id', 'department_id', 'branch_id', 'approval_level'] as $field) {
                    $data[$field] = $sourceMember?->$field;
                }
            } elseif ($mode === 'custom') {
                $role = IamRole::create([
                    'business_id' => $this->businessId(),
                    'name' => $data['custom_role_name'],
                    'slug' => Str::slug($data['custom_role_name'].'-'.Str::random(5)),
                    'landing_route' => 'dashboard',
                ]);
                $role->permissions()->sync($data['permissions'] ?? []);
                $data['iam_role_id'] = $role->id;
            }

            $membership = collect($data)->only(['iam_role_id', 'department_id', 'branch_id', 'approval_level'])->all();
            $membership['iam_role_id'] = $membership['iam_role_id'] ?? $this->defaultRoleId();
            $user = User::where('email', $data['email'])->first();
            $isNew = ! $user;

            if ($user) {
                $this->assertAttachableUser($user);
                $user->fill(collect($data)->only(['name', 'phone', 'employee_number', 'job_title', 'manager_id'])->filter(fn ($value) => $value !== null && $value !== '')->all())->save();
            } else {
                $userData = collect($data)
                    ->only(['name', 'email', 'username', 'employee_number', 'job_title', 'phone', 'manager_id'])
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->all();
                $userData['username'] = $userData['username'] ?? $this->uniqueUsername($data['name']);
                $userData['current_tenant_id'] = ActiveTenant::id();
                $userData['password'] = Hash::make(Str::random(64));
                $userData['role'] = 'staff';
                $userData['status'] = 'Pending Invitation';
                $userData['is_active'] = false;
                $userData['enable_password_login'] = true;
                $user = User::create($userData);
            }

            $this->syncProfileAccess($user, $membership, $isNew ? 'Pending Invitation' : ($user->status === 'Active' ? 'Active' : 'Pending Invitation'));

            if (! empty($data['photo']) && request()->hasFile('photo')) {
                $user->update(['photo_path' => request()->file('photo')->store('users/photos', 'public')]);
            }
            if (! empty($data['signature']) && request()->hasFile('signature')) {
                $user->update(['signature_path' => request()->file('signature')->store('users/signatures', 'public')]);
            }

            if ($mode === 'clone' && isset($source)) {
                $profileTeamIds = Team::where('business_id', $this->businessId())->pluck('id')->all();
                $user->teams()->sync($source->teams->pluck('id')->intersect($profileTeamIds));
                $user->update(['dashboard_layout' => $source->dashboard_layout, 'notification_preferences' => $source->notification_preferences]);
            } else {
                $user->teams()->sync($data['team_ids'] ?? []);
            }

            return $user->refresh();
        });
    }

    private function syncProfileAccess(User $user, array $membership, string $status): void
    {
        DB::table('tenant_user')->updateOrInsert(
            ['tenant_id' => ActiveTenant::id(), 'user_id' => $user->id],
            ['role' => $user->role === 'admin' ? 'owner' : 'staff', 'status' => strtolower($status) === 'active' ? 'active' : 'pending', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('business_user')->updateOrInsert(
            ['business_id' => $this->businessId(), 'user_id' => $user->id],
            $membership + ['status' => $status, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function roleForEmailPermissions(string $email, array $permissions): IamRole
    {
        $permissions = $this->validatedProfilePermissionIds($permissions);
        $slug = 'email-access-'.Str::slug(Str::before($email, '@')).'-'.$this->businessId();
        $role = IamRole::updateOrCreate(
            ['business_id' => $this->businessId(), 'slug' => $slug],
            ['name' => 'Access - '.$email, 'landing_route' => 'dashboard', 'is_system' => false]
        );
        $role->permissions()->sync($permissions);

        return $role;
    }

    private function sendInvitation(User $user): bool
    {
        $hours = SecuritySetting::where('business_id', $this->businessId())->first()?->invitation_expiry_hours ?? 24;

        UserInvitation::where('business_id', $this->businessId())
            ->where('user_id', $user->id)
            ->whereNull('accepted_at')
            ->where('status', 'Pending')
            ->update(['status' => 'Cancelled', 'cancelled_at' => now()]);

        $invite = UserInvitation::create([
            'business_id' => $this->businessId(),
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours($hours),
            'status' => 'Pending',
            'invited_by' => auth()->id(),
        ]);

        try {
            $this->outgoingMail->sendRaw(
                $user->email,
                'Activate '.$user->email.' for '.$this->profileName(),
                $this->activationEmailBody($user, $invite),
                businessId: $this->businessId(),
            );

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function activationUrl(UserInvitation $invitation): string
    {
        return route('administration.activate', $invitation->token);
    }

    private function activationEmailBody(User $user, UserInvitation $invitation): string
    {
        $membership = DB::table('business_user')
            ->where('business_id', $this->businessId())
            ->where('user_id', $user->id)
            ->first();
        $role = $membership?->iam_role_id ? IamRole::where('business_id', $this->businessId())->find($membership->iam_role_id)?->name : null;
        $department = $membership?->department_id ? Department::where('business_id', $this->businessId())->find($membership->department_id)?->name : null;
        $branch = $membership?->branch_id ? Branch::where('business_id', $this->businessId())->find($membership->branch_id)?->name : null;
        $expiresAt = $invitation->expires_at?->format('M d, Y H:i');

        return implode("\n", array_filter([
            'Hello '.$user->name.',',
            '',
            'You were invited to access a BAMA workspace.',
            'Workspace/profile: '.$this->profileName(),
            'Account email: '.$user->email,
            $role ? 'Profile access role: '.$role : null,
            $department ? 'Department: '.$department : null,
            $branch ? 'Branch: '.$branch : null,
            'Invited by: '.(auth()->user()?->name ?? config('app.name')),
            '',
            'Use this secure activation link to set your password and open only this profile:',
            $this->activationUrl($invitation),
            '',
            $expiresAt ? 'This link expires on '.$expiresAt.'.' : null,
            'If you were not expecting access to '.$this->profileName().', do not click the link.',
            '',
            'BAMA secure workspace access',
        ], fn ($line) => $line !== null));
    }

    private function profileUsers()
    {
        return User::where('role', '!=', 'client_portal')
            ->whereIn('id', $this->profileUserIds());
    }

    private function profileUserIds(): array
    {
        $ids = DB::table('business_user')->where('business_id', $this->businessId())->pluck('user_id')->all();

        if (auth()->id() && auth()->user()?->role === 'admin') {
            $ids[] = auth()->id();
        }

        return array_values(array_unique($ids));
    }

    private function assertProfileUser(User $user): void
    {
        if (in_array($user->id, $this->profileUserIds(), true)) {
            return;
        }

        $hasAnyProfile = DB::table('business_user')->where('user_id', $user->id)->exists();
        abort_if($hasAnyProfile, 404);
    }

    private function assertAttachableUser(User $user): void
    {
        $currentTenantId = ActiveTenant::id();
        $belongsToOtherTenant = DB::table('tenant_user')
            ->where('user_id', $user->id)
            ->where('tenant_id', '!=', $currentTenantId)
            ->exists();

        abort_if($belongsToOtherTenant, 422, 'That email already belongs to another organisation profile.');
    }

    private function detachFromCurrentProfile(User $user): void
    {
        $teamIds = Team::where('business_id', $this->businessId())->pluck('id')->all();
        $user->teams()->detach($teamIds);
        DB::table('business_user')->where(['business_id' => $this->businessId(), 'user_id' => $user->id])->delete();

        $hasTenantBusinesses = DB::table('business_user')
            ->join('businesses', 'businesses.id', '=', 'business_user.business_id')
            ->where('business_user.user_id', $user->id)
            ->where('businesses.tenant_id', ActiveTenant::id())
            ->exists();

        if (! $hasTenantBusinesses) {
            DB::table('tenant_user')->where(['tenant_id' => ActiveTenant::id(), 'user_id' => $user->id])->delete();
        }
    }

    private function defaultRoleId(): ?int
    {
        return IamRole::where('business_id', $this->businessId())->whereIn('slug', ['viewer', 'receptionist', 'staff'])->value('id')
            ?: IamRole::where('business_id', $this->businessId())->value('id');
    }

    private function profilePermissions(): Collection
    {
        if ($this->profilePermissionsCache) {
            return $this->profilePermissionsCache;
        }

        $names = $this->profilePermissionNames();

        return $this->profilePermissionsCache = IamPermission::query()
            ->whereIn('name', $names)
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');
    }

    private function profilePermissionIds(): array
    {
        if ($this->profilePermissionIdsCache !== null) {
            return $this->profilePermissionIdsCache;
        }

        return $this->profilePermissionIdsCache = $this->profilePermissions()
            ->flatten(1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function profilePermissionNames(): array
    {
        $names = collect(self::SHARED_PERMISSION_NAMES);
        $enabledModules = $this->profileEnabledModules();

        foreach ($enabledModules as $module) {
            $modulePermissions = collect($module->permissions ?? [])
                ->filter(fn ($permission) => is_string($permission) && $permission !== '');

            if ($modulePermissions->isEmpty()) {
                $modulePermissions = collect($this->permissionNamesForModuleSlug($module->slug));
            }

            $names = $names->merge($modulePermissions);
        }

        if ($enabledModules->isEmpty() && ! ActiveTenant::current()) {
            $names = collect(IamService::PERMISSIONS);
        }

        return $names->unique()->values()->all();
    }

    private function permissionNamesForModuleSlug(string $slug): array
    {
        $prefixes = collect([
            $slug,
            Str::before($slug, '-'),
            str_replace('-', '_', $slug),
        ])->filter()->unique()->values();

        return collect(IamService::PERMISSIONS)
            ->filter(fn ($permission) => $prefixes->contains(fn ($prefix) => Str::startsWith($permission, $prefix.'.')))
            ->values()
            ->all();
    }

    private function profileEnabledModules(): Collection
    {
        $tenant = ActiveTenant::current();
        $industry = ActiveBusiness::current()?->industry ?: $tenant?->industry;
        $modules = $this->modules->enabled($tenant);

        if (! $industry) {
            return $modules;
        }

        return $modules
            ->filter(fn ($module) => $module->type !== 'industry' || $this->moduleMatchesIndustry($module, $industry))
            ->values();
    }

    private function moduleMatchesIndustry(object $module, string $industry): bool
    {
        $industryKey = Str::slug(str_replace('_', '-', $industry));
        $moduleIndustryKey = $module->industry ? Str::slug(str_replace('_', '-', $module->industry)) : null;
        $moduleSlugKey = Str::slug(str_replace('_', '-', $module->slug));

        return $moduleIndustryKey === $industryKey
            || $moduleSlugKey === $industryKey
            || Str::startsWith($moduleSlugKey, $industryKey.'-')
            || Str::startsWith($industryKey, $moduleSlugKey.'-');
    }

    private function validatedProfilePermissionIds(array $permissions): array
    {
        $requested = collect($permissions)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $invalid = $requested->diff($this->profilePermissionIds());

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => 'One or more selected permissions are not available for this profile or industry.',
            ]);
        }

        return $requested->all();
    }

    private function permissionScopeLabel(): string
    {
        $business = ActiveBusiness::current();
        $tenant = ActiveTenant::current();
        $industry = $business?->industry ?: $tenant?->industry;
        $enabled = $this->profileEnabledModules()
            ->where('type', 'industry')
            ->pluck('name')
            ->filter()
            ->values();

        if ($enabled->isNotEmpty()) {
            return $enabled->join(', ');
        }

        return $industry ? Str::headline(str_replace(['_', '-'], ' ', $industry)) : 'Shared business modules';
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->limit(24, '')->value() ?: 'user';
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $counter++;
            $username = $base.'.'.$counter;
        }

        return $username;
    }

    private function businessId(): int
    {
        return (int) ActiveBusiness::id();
    }

    private function profileName(): string
    {
        return ActiveBusiness::current()?->name ?? config('app.name');
    }
}
