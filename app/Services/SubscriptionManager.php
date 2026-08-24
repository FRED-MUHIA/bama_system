<?php

namespace App\Services;

use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use App\Support\ActiveTenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SubscriptionManager
{
    public function active(?Tenant $tenant = null): bool
    {
        if (! Schema::hasTable('subscriptions')) {
            return true;
        }

        $tenant ??= ActiveTenant::current();
        $subscription = $tenant?->subscription;

        if (! $subscription) {
            return true;
        }

        $this->lockIfGraceExpired($tenant);

        return in_array($this->billingState($tenant)['state'], ['active', 'renewal_due', 'grace'], true);
    }

    public function billingState(?Tenant $tenant = null): array
    {
        $tenant ??= ActiveTenant::current();
        $subscription = $tenant?->subscription?->loadMissing('plan');

        if (! $tenant || ! $subscription) {
            return [
                'state' => 'active',
                'message' => null,
                'expires_at' => null,
                'grace_ends_at' => null,
            ];
        }

        if ($tenant->status === 'suspended' || $subscription->locked_at || in_array($subscription->status, ['paused', 'cancelled'], true)) {
            return [
                'state' => 'locked',
                'message' => 'This workspace is locked because the BAMA subscription is overdue. Renew the package to restore access.',
                'expires_at' => $subscription->renews_at,
                'grace_ends_at' => $subscription->grace_ends_at,
            ];
        }

        $expiresAt = $this->expiresAt($subscription);
        if (! $expiresAt) {
            return ['state' => 'active', 'message' => null, 'expires_at' => null, 'grace_ends_at' => null];
        }

        $graceEndsAt = $subscription->grace_ends_at ?: $expiresAt->copy()->addDays(2);
        $daysUntilExpiry = (int) Carbon::now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);

        if ($expiresAt->isPast()) {
            return [
                'state' => $graceEndsAt->isFuture() ? 'grace' : 'locked',
                'message' => $graceEndsAt->isFuture()
                    ? 'Your BAMA subscription has expired. You are in a 2-day grace period; renew now to keep access active.'
                    : 'This workspace is locked because the BAMA subscription is overdue. Renew the package to restore access.',
                'expires_at' => $expiresAt,
                'grace_ends_at' => $graceEndsAt,
            ];
        }

        if ($daysUntilExpiry <= 5) {
            return [
                'state' => 'renewal_due',
                'message' => "Your BAMA subscription expires in {$daysUntilExpiry} day".($daysUntilExpiry === 1 ? '' : 's').'. Renew before the grace period to avoid a lock.',
                'expires_at' => $expiresAt,
                'grace_ends_at' => $graceEndsAt,
            ];
        }

        return ['state' => 'active', 'message' => null, 'expires_at' => $expiresAt, 'grace_ends_at' => $graceEndsAt];
    }

    public function lockIfGraceExpired(?Tenant $tenant = null): bool
    {
        $tenant ??= ActiveTenant::current();
        $subscription = $tenant?->subscription;

        if (! $tenant || ! $subscription || $subscription->locked_at) {
            return false;
        }

        $expiresAt = $this->expiresAt($subscription);
        if (! $expiresAt) {
            return false;
        }

        $graceEndsAt = $subscription->grace_ends_at ?: $expiresAt->copy()->addDays(2);
        if ($graceEndsAt->isFuture()) {
            return false;
        }

        $subscription->forceFill([
            'status' => 'paused',
            'grace_ends_at' => $graceEndsAt,
            'locked_at' => now(),
            'ends_at' => $subscription->ends_at ?: $expiresAt,
        ])->save();

        $tenant->forceFill(['status' => 'suspended'])->save();

        return true;
    }

    public function limit(string $feature, ?Tenant $tenant = null): mixed
    {
        $subscription = ($tenant ?? ActiveTenant::current())?->subscription?->load('plan.features');
        $plan = $subscription?->plan;

        if (! $plan) {
            return null;
        }

        $featureRow = $plan->features->firstWhere('feature', $feature);

        return $featureRow?->limit ?? ($plan->limits[$feature] ?? null);
    }

    public function usage(string $feature, ?Tenant $tenant = null): int
    {
        if (! Schema::hasTable('subscription_usage')) {
            return 0;
        }

        $tenant ??= ActiveTenant::current();

        return $tenant ? (int) SubscriptionUsage::where('tenant_id', $tenant->id)->where('feature', $feature)->value('used') : 0;
    }

    public function allows(string $feature, int $increment = 1, ?Tenant $tenant = null): bool
    {
        $limit = $this->limit($feature, $tenant);

        if ($limit === null || $limit === true || $limit === 'true') {
            return true;
        }

        if ($limit === false || $limit === 'false') {
            return false;
        }

        return $this->usage($feature, $tenant) + $increment <= (int) $limit;
    }

    private function expiresAt($subscription): ?Carbon
    {
        return $subscription->status === 'trialing' && $subscription->trial_ends_at
            ? $subscription->trial_ends_at
            : ($subscription->renews_at ?: $subscription->trial_ends_at);
    }
}
