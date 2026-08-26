<?php

namespace App\Services;

use App\Models\DashboardWidget;
use App\Models\Tenant;
use App\Models\TenantDashboardWidget;
use App\Support\ActiveTenant;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;

class DashboardWidgetRegistry
{
    public function available(?Tenant $tenant = null): Collection
    {
        if (! SchemaCache::hasTable('dashboard_widgets')) {
            return collect();
        }

        $moduleRegistry = app(ModuleRegistry::class);

        return DashboardWidget::where('is_active', true)
            ->get()
            ->filter(fn ($widget) => ! $widget->module_slug || $moduleRegistry->enabledSlug($widget->module_slug, $tenant))
            ->values();
    }

    public function forUser(?int $userId = null, ?Tenant $tenant = null): Collection
    {
        $tenant ??= ActiveTenant::current();

        if (! $tenant || ! SchemaCache::hasTable('tenant_dashboard_widgets')) {
            return $this->available($tenant);
        }

        $configured = TenantDashboardWidget::with('widget')
            ->where('tenant_id', $tenant->id)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $userId ?? auth()->id()))
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->pluck('widget')
            ->filter();

        return $configured->isNotEmpty() ? $configured : $this->available($tenant);
    }
}
