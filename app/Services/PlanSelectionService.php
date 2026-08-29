<?php

namespace App\Services;

use App\Models\Plan;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;

class PlanSelectionService
{
    public function all(): Collection
    {
        $defaults = collect($this->defaults());

        if (! SchemaCache::hasTable('plans')) {
            return $defaults;
        }

        $order = ['starter' => 1, 'growth' => 2, 'professional' => 3, 'enterprise' => 4];
        $plans = Plan::query()->where('is_active', true)->get()->sortBy(fn (Plan $plan) => $order[$plan->slug] ?? 99)->values();

        if ($plans->isEmpty()) {
            return $defaults;
        }

        return $plans->map(function (Plan $plan) use ($defaults) {
            $fallback = $defaults->firstWhere('slug', $plan->slug) ?? [];
            $monthly = (float) $plan->monthly_price;

            return array_merge($fallback, [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'currency' => $plan->currency,
                'monthly_price' => $monthly,
                'annual_price' => $fallback['annual_price'] ?? round($monthly * 10, 2),
                'limits' => array_merge($fallback['limits'] ?? [], $plan->limits ?? []),
            ]);
        });
    }

    public function find(string $slug): array
    {
        return $this->all()->firstWhere('slug', $slug) ?? $this->all()->firstWhere('slug', 'starter');
    }

    public function slugs(): array
    {
        return $this->all()->pluck('slug')->all();
    }

    private function defaults(): array
    {
        return [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'currency' => 'KES',
                'monthly_price' => 0,
                'annual_price' => 0,
                'tagline' => 'For small teams validating their operating system.',
                'limits' => ['users' => 5, 'storage' => '5 GB', 'branches' => 1, 'projects' => 10, 'api_access' => false],
                'features' => ['CRM', 'Projects', 'Invoices', 'Reports', 'Client Portal'],
            ],
            [
                'slug' => 'growth',
                'name' => 'Growth',
                'currency' => 'KES',
                'monthly_price' => 9900,
                'annual_price' => 99000,
                'tagline' => 'For growing companies with multiple teams.',
                'limits' => ['users' => 25, 'storage' => '50 GB', 'branches' => 3, 'projects' => 100, 'api_access' => true],
                'features' => ['Everything in Starter', 'Inventory', 'Procurement', 'Approvals', 'Custom Branding'],
            ],
            [
                'slug' => 'professional',
                'name' => 'Professional',
                'currency' => 'KES',
                'monthly_price' => 24900,
                'annual_price' => 249000,
                'tagline' => 'For full operating teams that need finance control.',
                'limits' => ['users' => 100, 'storage' => '250 GB', 'branches' => 10, 'projects' => 500, 'api_access' => true],
                'features' => ['Everything in Growth', 'Finance', 'Accounting', 'Advanced Reporting', 'Industry Starter Package'],
                'highlight' => true,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'currency' => 'KES',
                'monthly_price' => 0,
                'annual_price' => 0,
                'tagline' => 'For complex organizations with custom controls.',
                'limits' => ['users' => 'Custom', 'storage' => 'Custom', 'branches' => 'Custom', 'projects' => 'Custom', 'api_access' => true],
                'features' => ['Custom Modules', 'Dedicated Support', 'SLA', 'Data Migration', 'Advanced Security'],
            ],
        ];
    }
}
