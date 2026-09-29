<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSubscriptionActive;
use App\Services\SubscriptionManager;
use Illuminate\Http\Request;
use Tests\TestCase;

class SubscriptionAccessMiddlewareTest extends TestCase
{
    public function test_expired_api_access_returns_payment_required_without_running_operation(): void
    {
        $this->mock(SubscriptionManager::class, function ($mock) {
            $mock->shouldReceive('active')->once()->andReturn(false);
            $mock->shouldReceive('billingState')->once()->andReturn(['message' => 'Renew to restore access.']);
        });
        $response = (new EnsureSubscriptionActive)->handle(Request::create('/api/v1/orders', 'POST'), function () {
            $this->fail('Expired subscriptions must not execute operations.');
        });
        $this->assertSame(402, $response->getStatusCode());
        $this->assertSame(route('billing.index'), $response->getData(true)['billing_url']);
    }

    public function test_paid_or_grace_access_can_continue(): void
    {
        $this->mock(SubscriptionManager::class, fn ($mock) => $mock->shouldReceive('active')->once()->andReturn(true));
        $response = (new EnsureSubscriptionActive)->handle(Request::create('/dashboard'), fn () => response('Allowed'));
        $this->assertSame('Allowed', $response->getContent());
    }

    public function test_workspace_routes_are_guarded_and_payment_routes_remain_open_for_renewal(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertContains('subscription.active', $routes->getByName('dashboard')->gatherMiddleware());
        $this->assertContains('subscription.active', $routes->getByName('api.v1.context')->gatherMiddleware());
        $this->assertNotContains('subscription.active', $routes->getByName('billing.index')->gatherMiddleware());
        $this->assertNotContains('subscription.active', $routes->getByName('api.payments.mpesa.callback')->gatherMiddleware());
    }
}
