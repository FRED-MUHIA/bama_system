<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class LoginErrorHandlingTest extends TestCase
{
    public function test_public_login_alias_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('app'));
        $this->assertNotNull($routes->getByName('app.login'));
        $this->assertNotNull($routes->getByName('public.platform.login'));
        $this->assertNotNull($routes->getByName('public.platform.login.store'));
        $this->assertNotNull($routes->getByName('public.portal.login'));
        $this->assertNotNull($routes->getByName('public.portal.login.store'));
        $this->assertNotNull($routes->getByName('public.login'));
        $this->assertNotNull($routes->getByName('public.login.store'));
    }

    public function test_custom_error_views_are_available(): void
    {
        $this->assertTrue(view()->exists('errors.404'));
        $this->assertTrue(view()->exists('errors.419'));
    }

    public function test_authenticated_token_mismatch_redirects_to_dashboard(): void
    {
        $request = Request::create('/login', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => new User(['role' => 'admin']));

        $response = app(ExceptionHandler::class)->render($request, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('dashboard'), $response->headers->get('Location'));
        $this->assertSame('That form session expired, but you are already signed in.', session('warning'));
    }
}
