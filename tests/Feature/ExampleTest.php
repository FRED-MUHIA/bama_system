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

    public function test_app_login_keeps_slide_flow_and_registration_entry(): void
    {
        $this->get(route('app.login'))
            ->assertOk()
            ->assertSee('Welcome Back')
            ->assertSee('Get Started')
            ->assertSee('Continue With Email')
            ->assertSee(route('register.account'), false)
            ->assertSee('data-app-screen', false)
            ->assertSee('data-app-go="1"', false)
            ->assertSee('data-app-go="2"', false)
            ->assertSee('Back to welcome')
            ->assertSee('Back to access options')
            ->assertSee('aria-controls="app-step-login"', false)
            ->assertSee('touchmove', false)
            ->assertSee('ArrowRight')
            ->assertSee('popstate')
            ->assertDontSee('Back home');
    }

    public function test_web_login_keeps_registration_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Create Account')
            ->assertSee(route('register.account'), false);
    }
}
