<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_restore_locked_subscription_with_audited_thirty_day_reset(): void
    {
        $this->travelTo(now()->startOfSecond());
        $owner = User::factory()->create(['role' => 'super_admin']);
        $tenant = Tenant::create(['name' => 'Locked client', 'slug' => 'locked-client', 'status' => 'suspended']);
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'monthly_price' => 1000, 'currency' => 'KES', 'is_active' => true]);
        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'status' => 'paused',
            'trial_ends_at' => now()->subDays(5), 'locked_at' => now()->subDays(3),
            'grace_ends_at' => now()->subDays(3), 'ends_at' => now()->subDays(5),
        ]);
        $payload = ['action' => 'reset_monthly', 'plan_id' => $plan->id, 'reason' => 'Gateway confirmation delayed: REF123'];
        $url = route('platform.tenants.subscription.update', $tenant);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->put($url, $payload)->assertForbidden();
        $this->actingAs($owner)->put($url, array_merge($payload, ['reason' => '']))->assertSessionHasErrors('reason');
        $this->put($url, $payload)->assertRedirect(route('platform.tenants'));
        $subscription->refresh();
        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertNull($subscription->locked_at);
        $this->assertNull($subscription->grace_ends_at);
        $this->assertTrue($subscription->accessExpiresAt()->equalTo(now()->addDays(30)));
        $this->assertSame($owner->id, $subscription->metadata['admin_adjustments'][0]['user_id']);
        $this->assertSame('paused', $subscription->metadata['admin_adjustments'][0]['before']['status']);
        $this->assertDatabaseCount('subscription_payments', 0);
    }
}
