<?php

namespace App\Services;

use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use App\Support\ActiveTenant;
use Illuminate\Support\Facades\Schema;

class SubscriptionManager
{
    public function active(?Tenant $tenant = null): bool
    {
        if (! Schema::hasTable('subscriptions')) {
            return true;
        }

        $subscription = ($tenant ?? ActiveTenant::current())?->subscription;

        return ! $subscription || $subscription->isActive();
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
}
