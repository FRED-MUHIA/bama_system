<?php

namespace App\Services;

use App\Models\DashboardWidget;
use App\Models\IndustryModule;
use App\Models\Tenant;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;

class IndustrySetupService
{
    private const IMPLEMENTED_INDUSTRIES = [
        'agriculture',
        'automotive',
        'construction',
        'fitness',
        'hospitality',
        'printing_branding',
        'real-estate',
        'retail',
        'salon',
    ];

    public function industries(): Collection
    {
        return collect($this->definitions());
    }

    public function implementedIndustries(): Collection
    {
        return $this->industries()
            ->whereIn('slug', self::IMPLEMENTED_INDUSTRIES)
            ->values();
    }

    public function slugs(): array
    {
        return $this->industries()->pluck('slug')->all();
    }

    public function implementedSlugs(): array
    {
        return $this->implementedIndustries()->pluck('slug')->all();
    }

    public function isImplemented(string $slug): bool
    {
        return in_array($this->normalizeIndustrySlug($slug), self::IMPLEMENTED_INDUSTRIES, true);
    }

    public function subIndustrySlugs(string $industry): array
    {
        return collect($this->find($industry)['sub_industries'] ?? [])->pluck('slug')->all();
    }

    public function registrationSubIndustrySlugs(string $industry): array
    {
        $definition = $this->find($industry);

        return $definition['registration_sub_industries'] ?? $this->subIndustrySlugs($industry);
    }

    public function find(string $slug): array
    {
        $slug = $this->normalizeIndustrySlug($slug);

        return $this->industries()->firstWhere('slug', $slug) ?? $this->industries()->firstWhere('slug', 'professional-services');
    }

    public function dashboardFeatures(string $industry, ?string $subIndustry = null): array
    {
        $definition = $this->find($industry);
        $sub = collect($definition['sub_industries'] ?? [])->firstWhere('slug', $subIndustry);

        return [
            'industry' => $definition['name'],
            'sub_industry' => $sub['name'] ?? null,
            'summary' => $sub['description'] ?? $definition['description'],
            'modules' => $definition['modules'] ?? [],
            'features' => $definition['features'] ?? [],
            'dashboard_features' => $sub['dashboard_features'] ?? $definition['dashboard_features'] ?? [],
            'reports' => $definition['reports'] ?? [],
            'roles' => $definition['roles'] ?? [],
            'menu_structure' => $definition['menu_structure'] ?? [],
        ];
    }

    public function package(string $industry, ?string $subIndustry = null): array
    {
        $definition = $this->find($industry);
        $dashboard = $this->dashboardFeatures($definition['slug'], $subIndustry);

        return [
            'industry' => $definition['name'],
            'slug' => $definition['slug'],
            'description' => $definition['description'] ?? null,
            'selected_sub_industry' => $subIndustry,
            'core_modules' => $definition['core_modules'] ?? [],
            'modules' => $definition['modules'] ?? [],
            'menus' => $definition['menus'] ?? [],
            'permissions' => $definition['permissions'] ?? [],
            'reports' => $definition['reports'] ?? [],
            'workflows' => $definition['workflows'] ?? [],
            'templates' => $definition['templates'] ?? [],
            'dashboard' => $dashboard,
            'dashboard_widgets' => $this->dashboardWidgets($definition['slug']),
            'registered_modules' => $this->registeredModules($definition['slug']),
            'sub_industries' => $definition['sub_industries'] ?? [],
            'api' => [
                'prefix' => '/api/v1/industries/'.$definition['slug'],
                'package' => route('api.v1.industry-packages.show', ['industry' => $definition['slug']], false),
                'dashboard' => route('api.v1.industry-packages.dashboard', ['industry' => $definition['slug']], false),
            ],
            'supports' => [
                'tenant_isolation' => true,
                'role_permissions' => true,
                'dynamic_menus' => true,
                'dashboard_widgets' => true,
                'reports' => true,
                'api_endpoints' => true,
                'subscription_activation' => true,
            ],
        ];
    }

    public function dashboardFeaturesForTenant(?Tenant $tenant): array
    {
        if (! $tenant) {
            return [];
        }

        $settings = $tenant->settings ?? [];
        $industry = $this->normalizeIndustrySlug($tenant->industry ?: ($settings['industry'] ?? 'professional-services'));
        $subIndustry = $tenant->sub_industry ?? $settings['sub_industry'] ?? null;

        return $this->dashboardFeatures($industry, $subIndustry);
    }

    public function provision(Tenant $tenant): void
    {
        app(ModuleRegistry::class)->enableDefaultsFor($tenant);
        $this->initializeDashboard($tenant);
    }

    public function onboardingChecklist(): array
    {
        return [
            'Complete Company Profile',
            'Upload Logo',
            'Invite Team Members',
            'Add First Customer',
            'Create First Project',
            'Configure Finance',
        ];
    }

    private function initializeDashboard(Tenant $tenant): void
    {
        if (! SchemaCache::hasTable('dashboard_widgets') || ! SchemaCache::hasTable('tenant_dashboard_widgets')) {
            return;
        }

        DashboardWidget::query()
            ->where('is_active', true)
            ->where(function ($query) use ($tenant) {
                $query->whereNull('industry')->orWhere('industry', $tenant->industry);
            })
            ->limit(8)
            ->get()
            ->each(function (DashboardWidget $widget, int $index) use ($tenant) {
                $tenant->dashboardWidgets()->updateOrCreate(
                    ['dashboard_widget_id' => $widget->id, 'user_id' => null],
                    ['sort_order' => $index + 1, 'width' => 4, 'enabled' => true]
                );
            });
    }

    private function dashboardWidgets(string $industry): array
    {
        if (! SchemaCache::hasTable('dashboard_widgets')) {
            return [];
        }

        return DashboardWidget::query()
            ->where('is_active', true)
            ->where(function ($query) use ($industry) {
                $query->whereNull('industry')->orWhere('industry', $industry);
            })
            ->orderBy('industry')
            ->orderBy('name')
            ->get(['slug', 'name', 'module_slug', 'industry', 'component', 'permission'])
            ->map(fn (DashboardWidget $widget) => $widget->toArray())
            ->all();
    }

    private function registeredModules(string $industry): array
    {
        if (! SchemaCache::hasTable('industry_modules')) {
            return [];
        }

        return IndustryModule::query()
            ->with('module:id,slug,name,type,industry,icon,route,permissions,menu,widgets,is_core,is_active')
            ->where('industry', $industry)
            ->where('enabled_by_default', true)
            ->get()
            ->pluck('module')
            ->filter()
            ->values()
            ->map(fn ($module) => $module->toArray())
            ->all();
    }

    private function definitions(): array
    {
        return config('industry-packages.industries', []);
    }

    private function normalizeIndustrySlug(?string $industry): string
    {
        $slug = str($industry ?: 'professional-services')->snake(' ')->slug('-')->toString();

        return [
            'professionalservices' => 'professional-services',
            'professional-services' => 'professional-services',
            'salon-spa' => 'salon',
            'salon-and-spa' => 'salon',
            'fitness-gym' => 'fitness',
            'realestate' => 'real-estate',
            'real-estate' => 'real-estate',
            'accountingfirm' => 'accounting-firm',
            'accounting-firm' => 'accounting-firm',
            'printing-branding' => 'printing_branding',
            'printingbranding' => 'printing_branding',
        ][$slug] ?? ($slug ?: 'professional-services');
    }
}
