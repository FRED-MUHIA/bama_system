<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Tenant;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToBusiness
{
    private static array $businessScopeTableExists = [];

    private static array $businessScopeColumnExists = [];

    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder) {
            $model = $builder->getModel();
            $table = $model->getTable();
            $tenantId = ActiveTenant::id();

            if (self::scopeHasColumn($table, 'tenant_id') && $tenantId) {
                $builder->where($table.'.tenant_id', $tenantId);
            } elseif (self::scopeHasColumn($table, 'tenant_id') && auth()->check() && auth()->user()?->role !== 'super_admin') {
                $builder->whereRaw('1 = 0');
            }

            $businessId = ActiveBusiness::id();

            if ($businessId && self::scopeHasColumn($table, 'business_id')) {
                $builder->where($table.'.business_id', $businessId);
            } elseif (self::scopeHasColumn($table, 'business_id') && auth()->check() && auth()->user()?->role !== 'super_admin') {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function ($model) {
            if (self::scopeHasColumn($model->getTable(), 'tenant_id') && empty($model->tenant_id)) {
                $model->tenant_id = ActiveTenant::id();
            }

            if (empty($model->business_id)) {
                $model->business_id = ActiveBusiness::id();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    private static function scopeHasColumn(string $table, string $column): bool
    {
        if (! (self::$businessScopeTableExists[$table] ??= Schema::hasTable($table))) {
            return false;
        }

        $key = $table.'.'.$column;

        return self::$businessScopeColumnExists[$key] ??= Schema::hasColumn($table, $column);
    }
}
