<?php

namespace Tests\Feature;

use App\Models\Subscription;
use Tests\TestCase;

class SubscriptionAdminPeriodTest extends TestCase
{
    public function test_admin_period_overrides_old_trial_and_preserves_time_on_early_payment(): void
    {
        $this->travelTo(now()->startOfSecond());
        $expiry = now()->addDays(10);
        $subscription = new Subscription([
            'status' => 'active', 'trial_ends_at' => now()->subDays(5),
            'renews_at' => $expiry,
            'metadata' => ['admin_access_expires_at' => $expiry->toDateTimeString()],
        ]);
        $this->assertTrue($subscription->accessExpiresAt()->equalTo($expiry));
        $this->assertTrue($subscription->nextMonthlyRenewalAt(now(), 1)->equalTo($expiry->copy()->addDays(30)));
        $subscription->status = 'past_due';
        $this->assertTrue($subscription->accessExpiresAt()->equalTo($expiry));
        $subscription->status = 'trialing';
        $this->assertTrue($subscription->accessExpiresAt()->equalTo(now()->subDays(5)));
    }
}
