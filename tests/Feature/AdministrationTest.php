<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\IamRole;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\IamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::where('slug', 'bama')->firstOrFail();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($this->admin)->withSession(['active_business_id' => $this->business->id]);

        app(IamService::class)->bootstrap();
    }

    public function test_administration_dashboard_and_default_roles_render(): void
    {
        $this->get(route('administration.index'))
            ->assertOk()
            ->assertSee('Administration')
            ->assertSee('System Administrator')
            ->assertSee('Roles & Permissions');

        $this->assertGreaterThanOrEqual(13, IamRole::where('business_id', $this->business->id)->count());
    }

    public function test_admin_invites_user_without_knowing_password_and_user_activates(): void
    {
        $this->flushArrayMail();
        $role = IamRole::where('business_id', $this->business->id)->where('slug', 'viewer')->firstOrFail();

        $this->post(route('administration.users.store'), [
            'name' => 'Invited User',
            'email' => 'invite@example.test',
            'username' => 'invited',
            'iam_role_id' => $role->id,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'invite@example.test')->firstOrFail();
        $this->assertSame('Pending Invitation', $user->status);
        $this->assertSame($this->business->tenant_id, $user->current_tenant_id);
        $this->assertCount(1, $this->arrayMailMessages());
        $this->assertSame('invite@example.test', $this->arrayMailMessages()->first()->getEnvelope()->getRecipients()[0]->getAddress());

        $invite = UserInvitation::where('user_id', $user->id)->firstOrFail();
        $this->post(route('administration.activate.store', $invite->token), [
            'password' => 'Strong!Pass123',
            'password_confirmation' => 'Strong!Pass123',
            'terms' => 1,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Strong!Pass123', $user->fresh()->password));
        $this->assertSame('Active', $user->fresh()->status);
        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $this->business->tenant_id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('business_user', [
            'business_id' => $this->business->id,
            'user_id' => $user->id,
            'status' => 'Active',
        ]);
    }

    public function test_activated_employee_logs_into_the_assigned_profile(): void
    {
        $role = IamRole::where('business_id', $this->business->id)->where('slug', 'viewer')->firstOrFail();
        $this->post(route('administration.users.store'), [
            'name' => 'Profile Employee',
            'email' => 'profile.employee@example.test',
            'iam_role_id' => $role->id,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'profile.employee@example.test')->firstOrFail();
        $invite = UserInvitation::where('user_id', $user->id)->firstOrFail();
        $this->post(route('administration.activate.store', $invite->token), [
            'password' => 'Strong!Pass123',
            'password_confirmation' => 'Strong!Pass123',
            'terms' => 1,
        ])->assertRedirect(route('login'));

        auth()->logout();
        $this->post(route('login.store'), [
            'username' => 'profile.employee@example.test',
            'password' => 'Strong!Pass123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame($this->business->tenant_id, session('active_tenant_id'));
        $this->assertSame($this->business->id, session('active_business_id'));
    }

    public function test_package_user_limit_blocks_new_profile_members(): void
    {
        $starter = Plan::where('slug', 'starter')->firstOrFail();
        DB::table('subscriptions')
            ->where('tenant_id', $this->business->tenant_id)
            ->update(['plan_id' => $starter->id]);

        $role = IamRole::where('business_id', $this->business->id)->where('slug', 'viewer')->firstOrFail();

        User::factory()
            ->count(4)
            ->create(['role' => 'staff', 'is_active' => true, 'status' => 'Active'])
            ->each(function (User $user) use ($role) {
                DB::table('business_user')->insert([
                    'business_id' => $this->business->id,
                    'user_id' => $user->id,
                    'iam_role_id' => $role->id,
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        $this->post(route('administration.users.store'), [
            'name' => 'Sixth Person',
            'email' => 'sixth@example.test',
            'iam_role_id' => $role->id,
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'sixth@example.test']);
    }

    public function test_existing_profile_member_can_be_modified_when_package_is_full(): void
    {
        $starter = Plan::where('slug', 'starter')->firstOrFail();
        DB::table('subscriptions')
            ->where('tenant_id', $this->business->tenant_id)
            ->update(['plan_id' => $starter->id]);

        $role = IamRole::where('business_id', $this->business->id)->where('slug', 'viewer')->firstOrFail();
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true, 'status' => 'Active']);
        DB::table('business_user')->insert([
            'business_id' => $this->business->id,
            'user_id' => $user->id,
            'iam_role_id' => $role->id,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::factory()
            ->count(3)
            ->create(['role' => 'staff', 'is_active' => true, 'status' => 'Active'])
            ->each(function (User $seat) use ($role) {
                DB::table('business_user')->insert([
                    'business_id' => $this->business->id,
                    'user_id' => $seat->id,
                    'iam_role_id' => $role->id,
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        $this->put(route('administration.users.update', $user), [
            'name' => 'Updated Employee',
            'employee_number' => null,
            'job_title' => 'Accountant',
            'phone' => '0712345678',
            'manager_id' => null,
            'iam_role_id' => $role->id,
            'department_id' => null,
            'branch_id' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Updated Employee', $user->fresh()->name);
        $this->assertSame('Accountant', $user->fresh()->job_title);
    }

    public function test_viewer_is_denied_administration_access(): void
    {
        $viewer = User::factory()->create(['role' => 'staff', 'is_active' => true, 'status' => 'Active']);
        $role = IamRole::where('business_id', $this->business->id)->where('slug', 'viewer')->first();
        DB::table('business_user')->insert([
            'business_id' => $this->business->id,
            'user_id' => $viewer->id,
            'iam_role_id' => $role->id,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($viewer)->withSession(['active_business_id' => $this->business->id])
            ->get(route('administration.index'))
            ->assertForbidden();
    }

    public function test_failed_logins_lock_account_and_unlock_control_restores_it(): void
    {
        auth()->logout();
        $user = User::factory()->create([
            'username' => 'lockme',
            'password' => Hash::make('Correct!123'),
            'is_active' => true,
            'status' => 'Active',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['username' => 'lockme', 'password' => 'wrong']);
        }

        $this->assertNotNull($user->fresh()->locked_at);

        $this->actingAs($this->admin)->withSession(['active_business_id' => $this->business->id])
            ->post(route('administration.users.unlock', $user))
            ->assertRedirect();

        $this->assertNull($user->fresh()->locked_at);
    }

    public function test_admin_can_delete_pending_or_suspended_setup_users_but_not_active_user(): void
    {
        $pending = User::factory()->create(['status' => 'Pending Invitation', 'is_active' => false]);
        $suspended = User::factory()->create(['status' => 'Suspended', 'is_active' => false]);
        $active = User::factory()->create(['status' => 'Active', 'is_active' => true]);

        $this->delete(route('administration.users.destroy', $pending), ['confirm_delete' => 1])->assertSessionHas('status');
        $this->delete(route('administration.users.destroy', $suspended), ['confirm_delete' => 1])->assertSessionHas('status');
        $this->assertModelMissing($pending);
        $this->assertModelMissing($suspended);

        $this->delete(route('administration.users.destroy', $active), ['confirm_delete' => 1])->assertSessionHas('warning');
        $this->assertModelExists($active);
    }

    public function test_admin_generates_and_emails_single_use_recovery_link(): void
    {
        $this->flushArrayMail();
        $user = User::factory()->create(['status' => 'Active', 'is_active' => true]);

        $response = $this->post(route('administration.users.recovery-link', $user));

        $response->assertSessionHas('status')->assertSessionHas('recovery_link');
        $this->assertCount(1, $this->arrayMailMessages());
        $this->assertSame($user->email, $this->arrayMailMessages()->first()->getEnvelope()->getRecipients()[0]->getAddress());

        $link = session('recovery_link');
        $token = basename(parse_url($link, PHP_URL_PATH));
        $this->get($link)->assertOk()->assertSee('One-time account recovery');
        $this->post(route('administration.recovery.store', $token), [
            'password' => 'Recovered!123',
            'password_confirmation' => 'Recovered!123',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('Recovered!123', $user->fresh()->password));
        $this->get($link)->assertNotFound();
    }

    public function test_user_profile_update_cannot_change_access_assignments(): void
    {
        $roleBefore = DB::table('business_user')->where('user_id', $this->admin->id)->value('iam_role_id');

        $this->put(route('profile.update'), [
            'phone' => '0700000000',
            'preferred_language' => 'sw',
            'timezone' => 'Africa/Nairobi',
            'iam_role_id' => 999,
        ])->assertSessionHasNoErrors();

        $this->assertSame('0700000000', $this->admin->fresh()->phone);
        $this->assertSame($roleBefore, DB::table('business_user')->where('user_id', $this->admin->id)->value('iam_role_id'));
    }

    public function test_admin_can_view_user_presence_and_concise_activity(): void
    {
        DB::table('sessions')->insert([
            'id' => 'online-session',
            'user_id' => $this->admin->id,
            'ip_address' => null,
            'user_agent' => null,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
        app(IamService::class)->audit('activity:invoices.store');

        $this->get(route('administration.index'))
            ->assertOk()
            ->assertSee('Team presence')
            ->assertSee('Online');
        $this->get(route('administration.users.activity', $this->admin))
            ->assertOk()
            ->assertSee('Major activity')
            ->assertSee('Created invoices')
            ->assertDontSee('127.0.0.1');
    }

    private function flushArrayMail(): void
    {
        app('mail.manager')->mailer()->getSymfonyTransport()->flush();
    }

    private function arrayMailMessages()
    {
        return app('mail.manager')->mailer()->getSymfonyTransport()->messages();
    }
}
