<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
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
        $user = User::factory()->create(['email' => 'signin@example.test', 'password' => Hash::make('Strong!Pass123'), 'is_active' => true, 'status' => 'Active']);

        $this->post(route('login.store'), ['username' => $user->email, 'password' => 'Strong!Pass123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
