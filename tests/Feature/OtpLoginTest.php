<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_sent_otp_shows_verification_form_and_resend_countdown(): void
    {
        Mail::fake();
        RateLimiter::clear('login-otp:'.sha1('otp@example.test|127.0.0.1'));
        User::factory()->create(['email' => 'otp@example.test', 'is_active' => true, 'enable_otp_login' => true]);

        $response = $this->post(route('login.otp.request'), ['email' => 'otp@example.test']);

        $response->assertSessionHas('otp_sent', true)->assertSessionHas('otp_email', 'otp@example.test');
        $this->get(route('login'))->assertOk()->assertSee('OTP sent')->assertSee('Resend OTP in');
    }

    public function test_user_can_sign_in_with_email_and_password(): void
    {
        $user = User::factory()->create(['email' => 'signin@example.test', 'password' => Hash::make('Strong!Pass123'), 'role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->assignToDefaultWorkspace($user);

        $this->post(route('login.store'), ['username' => $user->email, 'password' => 'Strong!Pass123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_expired_login_form_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->assignToDefaultWorkspace($user);

        $this->withMiddleware()
            ->actingAs($user)
            ->withSession(['_token' => 'fresh-token'])
            ->post(route('login.store'), [
                '_token' => 'stale-token',
                'username' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning', 'That form session expired, but you are already signed in.');
    }

    private function assignToDefaultWorkspace(User $user): void
    {
        $business = Business::where('slug', 'bama')->firstOrFail();

        $user->forceFill(['current_tenant_id' => $business->tenant_id])->save();

        DB::table('tenant_user')->updateOrInsert(
            ['tenant_id' => $business->tenant_id, 'user_id' => $user->id],
            ['role' => 'owner', 'status' => 'active', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        );

        DB::table('business_user')->updateOrInsert(
            ['business_id' => $business->id, 'user_id' => $user->id],
            ['status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
        );
    }
}
