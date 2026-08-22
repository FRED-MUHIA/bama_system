<?php

namespace App\Models;

use App\Support\ActiveTenant;
use App\Support\SchemaCache;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantModule extends Pivot
{
    protected $table = 'tenant_modules';

    public $incrementing = true;

    protected $fillable = ['tenant_id', 'module_id', 'enabled', 'settings', 'enabled_at'];

    protected $casts = ['enabled' => 'boolean', 'settings' => 'array', 'enabled_at' => 'datetime'];

    public static function enabled(string $slug, ?Tenant $tenant = null): bool
    {
        if (! SchemaCache::hasTable('tenant_modules') || ! SchemaCache::hasTable('modules')) {
            return true;
        }

        $tenantId = $tenant?->id ?? ActiveTenant::id();
        if (! $tenantId) {
            return true;
        }

        return static::query()
            ->join('modules', 'modules.id', '=', 'tenant_modules.module_id')
            ->where('tenant_modules.tenant_id', $tenantId)
            ->where('modules.slug', $slug)
            ->where('modules.is_active', true)
            ->where('tenant_modules.enabled', true)
            ->exists();
    }
}
