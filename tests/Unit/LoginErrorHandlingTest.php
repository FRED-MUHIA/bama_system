<?php

namespace Tests\Unit;

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
}
