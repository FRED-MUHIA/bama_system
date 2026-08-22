<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantTheme;
use App\Support\ActiveTenant;
use App\Support\SchemaCache;

class ThemeManager
{
    private static array $themes = [];

    public function current(?Tenant $tenant = null): ?TenantTheme
    {
        if (! SchemaCache::hasTable('tenant_themes')) {
            return null;
        }

        $tenant ??= ActiveTenant::current();
        $cacheKey = $tenant?->id ?: 'none';

        if (array_key_exists($cacheKey, self::$themes)) {
            return self::$themes[$cacheKey];
        }

        return self::$themes[$cacheKey] = $tenant ? TenantTheme::where('tenant_id', $tenant->id)->first() : null;
    }

    public function cssVariables(?Tenant $tenant = null): string
    {
        $theme = $this->current($tenant);
        $primary = $theme?->primary_color ?: '#00A651';
        $secondary = $theme?->secondary_color ?: '#000000';
        $accent = $theme?->accent_color ?: $primary;

        return "--tenant-primary: {$primary}; --tenant-secondary: {$secondary}; --tenant-accent: {$accent}; --bama-orange: {$primary}; --bama-orange-dark: {$accent}; --bama-black: {$secondary};";
    }
}
