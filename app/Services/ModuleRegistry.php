<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Support\ActiveTenant;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;

class ModuleRegistry
{
    private static array $enabled = [];
    private static array $enabledSlugs = [];

    public static function flushCache(): void
    {
        self::$enabled = [];
        self::$enabledSlugs = [];
    }

    public function enabled(?Tenant $tenant = null): Collection
    {
        if (! SchemaCache::hasTable('modules')) {
            return collect();
        }

        $tenant ??= ActiveTenant::current();
        $cacheKey = $tenant?->id ?: 'core';

        if (array_key_exists($cacheKey, self::$enabled)) {
            return self::$enabled[$cacheKey];
        }

        if (! $tenant || ! SchemaCache::hasTable('tenant_modules')) {
            return self::$enabled[$cacheKey] = Module::where('is_active', true)->where('is_core', true)->orderBy('name')->get();
        }

        return self::$enabled[$cacheKey] = Module::query()
            ->join('tenant_modules', 'tenant_modules.module_id', '=', 'modules.id')
            ->where('tenant_modules.tenant_id', $tenant->id)
            ->where('tenant_modules.enabled', true)
            ->where('modules.is_active', true)
            ->select('modules.*')
            ->orderBy('modules.type')
            ->orderBy('modules.name')
            ->get();
    }

    public function enabledSlug(string $slug, ?Tenant $tenant = null): bool
    {
        $tenant ??= ActiveTenant::current();

        if (! $tenant || ! SchemaCache::hasTable('tenant_modules') || ! SchemaCache::hasTable('modules')) {
            return true;
        }

        $tenantKey = (string) $tenant->id;
        if (! array_key_exists($tenantKey, self::$enabledSlugs)) {
            self::$enabledSlugs[$tenantKey] = TenantModule::query()
                ->join('modules', 'modules.id', '=', 'tenant_modules.module_id')
                ->where('tenant_modules.tenant_id', $tenant->id)
                ->where('modules.is_active', true)
                ->where('tenant_modules.enabled', true)
                ->pluck('modules.slug')
                ->all();
        }

        return in_array($slug, self::$enabledSlugs[$tenantKey], true);
    }

    public function enableDefaultsFor(Tenant $tenant): void
    {
        if (! SchemaCache::hasTable('modules') || ! SchemaCache::hasTable('tenant_modules')) {
            return;
        }

        $industryModuleIds = SchemaCache::hasTable('industry_modules')
            ? \App\Models\IndustryModule::where('industry', $tenant->industry)->where('enabled_by_default', true)->pluck('module_id')
            : collect();

        $modules = Module::where('is_core', true)->orWhereIn('id', $industryModuleIds)->get();

        foreach ($modules as $module) {
            $tenant->modules()->syncWithoutDetaching([
                $module->id => ['enabled' => true, 'enabled_at' => now()],
            ]);
        }
    }
}
