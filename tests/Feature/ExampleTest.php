<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_guest_root_opens_the_public_homepage(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Run Your Entire Business From One Unified Platform')
            ->assertDontSee('Bama app login', false)
            ->assertDontSee('href="/app/login"', false);
    }

    public function test_authenticated_root_opens_the_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_app_login_keeps_slide_flow_without_registration_entry(): void
    {
        $this->get(route('app.login'))
            ->assertOk()
            ->assertSee('Workspace<br>Console', false)
            ->assertSee('Workspace Sign In')
            ->assertSee('Get Started')
            ->assertSee('Continue to Sign In')
            ->assertDontSee(route('register.account'), false)
            ->assertDontSee('Register an account')
            ->assertDontSee('Create business account')
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
