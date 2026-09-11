<?php

namespace Tests\Feature;

use App\Models\User;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_root_opens_the_app_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('app.login'));
    }

    public function test_authenticated_root_opens_the_dashboard(): void
    {
        $user = User::factory()->make(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_app_login_keeps_registration_entry_without_home_panels(): void
    {
        $this->get(route('app.login'))
            ->assertOk()
            ->assertSee('Welcome Back')
            ->assertSee('Get Started')
            ->assertSee(route('register.account'), false)
            ->assertDontSee('Continue with email');
    }

    public function test_web_login_keeps_registration_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Create Account')
            ->assertSee(route('register.account'), false);
    }
}
