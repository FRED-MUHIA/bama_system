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

    public function test_app_login_does_not_render_home_or_signup_panels(): void
    {
        $this->get(route('app.login'))
            ->assertOk()
            ->assertSee('Welcome Back')
            ->assertDontSee('Get Started')
            ->assertDontSee('Continue with email')
            ->assertDontSee('Create Account');
    }
}
