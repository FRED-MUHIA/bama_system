<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $now = now();

        foreach (config('industry-packages.core_modules', []) as $slug => $name) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'namespace' => 'Modules\\Core',
                    'type' => 'core',
                    'industry' => null,
                    'icon' => $this->iconFor($slug),
                    'route' => $this->routeFor($slug),
                    'permissions' => json_encode([$slug.'.view', $slug.'.manage']),
                    'menu' => json_encode($this->menuFor($name, $slug, 'Core')),
                    'widgets' => json_encode([$slug.'-summary']),
                    'is_core' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        foreach (config('industry-packages.industries', []) as $industry) {
            $industrySlug = $industry['slug'];
            $folder = $this->folderFor($industrySlug);

            $packageId = $this->upsertModule([
                'slug' => $industrySlug,
                'name' => $industry['name'],
                'namespace' => 'Modules\\'.$folder,
                'type' => 'industry',
                'industry' => $industrySlug,
                'icon' => $this->industryIconFor($industrySlug),
                'route' => null,
                'permissions' => $industry['permissions'] ?? [$industrySlug.'.view', $industrySlug.'.manage'],
                'menu' => [
                    'label' => $industry['name'],
                    'group' => 'Industry',
                    'icon' => $this->industryIconFor($industrySlug),
                    'route' => null,
                ],
                'widgets' => [$industrySlug.'-overview', $industrySlug.'-reports'],
                'is_core' => false,
                'is_active' => true,
            ]);

            $this->attachIndustryModule($industrySlug, $packageId);
            $this->upsertWidget($industrySlug.'-overview', $industry['name'].' Overview', $industrySlug, $industrySlug, null);
            $this->upsertWidget($industrySlug.'-reports', $industry['name'].' Reports', 'reporting', $industrySlug, $industrySlug.'.reports');

            foreach ($industry['modules'] ?? [] as $moduleName) {
                $moduleSlug = Str::slug($industrySlug.'-'.$moduleName);

                $moduleId = $this->upsertModule([
                    'slug' => $moduleSlug,
                    'name' => $moduleName,
                    'namespace' => 'Modules\\'.$folder,
                    'type' => 'industry',
                    'industry' => $industrySlug,
                    'icon' => $this->industryIconFor($industrySlug),
                    'route' => null,
                    'permissions' => [$moduleSlug.'.view', $moduleSlug.'.manage', $moduleSlug.'.reports'],
                    'menu' => [
                        'label' => $moduleName,
                        'group' => $industry['name'],
                        'icon' => $this->industryIconFor($industrySlug),
                        'route' => null,
                    ],
                    'widgets' => [$moduleSlug.'-summary'],
                    'is_core' => false,
                    'is_active' => true,
                ]);

                $this->attachIndustryModule($industrySlug, $moduleId);
                $this->upsertWidget($moduleSlug.'-summary', $moduleName.' Summary', $moduleSlug, $industrySlug, $moduleSlug.'.view');
            }
        }
    }

    public function down(): void
    {
        // Registry data is intentionally retained. Removing rows would disable modules for existing tenants.
    }

    private function upsertModule(array $module): int
    {
        $now = now();

        DB::table('modules')->updateOrInsert(
            ['slug' => $module['slug']],
            [
                'name' => $module['name'],
                'namespace' => $module['namespace'],
                'type' => $module['type'],
                'industry' => $module['industry'],
                'icon' => $module['icon'],
                'route' => $module['route'],
                'permissions' => json_encode($module['permissions']),
                'menu' => json_encode($module['menu']),
                'widgets' => json_encode($module['widgets']),
                'is_core' => $module['is_core'],
                'is_active' => $module['is_active'],
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        return (int) DB::table('modules')->where('slug', $module['slug'])->value('id');
    }

    private function attachIndustryModule(string $industry, int $moduleId): void
    {
        if (! Schema::hasTable('industry_modules')) {
            return;
        }

        DB::table('industry_modules')->updateOrInsert(
            ['industry' => $industry, 'module_id' => $moduleId],
            [
                'enabled_by_default' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function upsertWidget(string $slug, string $name, ?string $moduleSlug, ?string $industry, ?string $permission): void
    {
        if (! Schema::hasTable('dashboard_widgets')) {
            return;
        }

        DB::table('dashboard_widgets')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => $name,
                'module_slug' => $moduleSlug,
                'industry' => $industry,
                'component' => 'dashboard.widgets.metric-card',
                'permission' => $permission,
                'settings_schema' => json_encode([
                    'supports_tenant_filters' => true,
                    'supports_period_filters' => true,
                ]),
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function menuFor(string $name, string $slug, string $group): array
    {
        return [
            'label' => $name,
            'group' => $group,
            'icon' => $this->iconFor($slug),
            'route' => $this->routeFor($slug),
        ];
    }

    private function routeFor(string $slug): ?string
    {
        return [
            'crm' => 'clients.index',
            'projects' => 'projects.index',
            'finance' => 'finance.index',
            'accounting' => 'accounting.index',
            'expenses' => 'accounting.index',
            'documents' => 'quotations.index',
            'reporting' => 'erp.reports',
            'administration' => 'administration.index',
            'portal' => 'erp.portal',
        ][$slug] ?? null;
    }

    private function iconFor(string $slug): string
    {
        return [
            'crm' => 'bi-people',
            'projects' => 'bi-kanban',
            'finance' => 'bi-bank',
            'accounting' => 'bi-diagram-3',
            'expenses' => 'bi-receipt',
            'documents' => 'bi-file-earmark-text',
            'reporting' => 'bi-bar-chart',
            'hr' => 'bi-person-badge',
            'administration' => 'bi-gear',
            'portal' => 'bi-window-sidebar',
            'notifications' => 'bi-bell',
        ][$slug] ?? 'bi-grid';
    }

    private function industryIconFor(string $slug): string
    {
        return [
            'construction' => 'bi-building',
            'real-estate' => 'bi-house-door',
            'healthcare' => 'bi-heart-pulse',
            'education' => 'bi-mortarboard',
            'university' => 'bi-bank2',
            'retail' => 'bi-shop',
            'wholesale' => 'bi-box-seam',
            'manufacturing' => 'bi-gear-wide-connected',
            'hospitality' => 'bi-cup-hot',
            'restaurant' => 'bi-cup-straw',
            'logistics' => 'bi-truck',
            'transport' => 'bi-bus-front',
            'professional-services' => 'bi-briefcase',
            'legal' => 'bi-journal-text',
            'accounting-firm' => 'bi-calculator',
            'insurance' => 'bi-shield-check',
            'banking' => 'bi-cash-coin',
            'microfinance' => 'bi-piggy-bank',
            'ngo' => 'bi-globe2',
            'government' => 'bi-building-check',
            'agriculture' => 'bi-flower1',
            'pharmacy' => 'bi-capsule',
            'media' => 'bi-megaphone',
            'telecom' => 'bi-broadcast-pin',
            'automotive' => 'bi-car-front',
            'fitness' => 'bi-activity',
            'salon' => 'bi-scissors',
            'events' => 'bi-calendar-event',
        ][$slug] ?? 'bi-grid';
    }

    private function folderFor(string $slug): string
    {
        return [
            'real-estate' => 'RealEstate',
            'professional-services' => 'ProfessionalServices',
            'accounting-firm' => 'AccountingFirm',
        ][$slug] ?? Str::studly($slug);
    }
};
