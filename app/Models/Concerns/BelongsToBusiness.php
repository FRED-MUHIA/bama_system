<?php

namespace App\Models\Concerns;

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
                $builder->where(function (Builder $query) use ($table, $tenantId) {
                    $query->where($table . '.tenant_id', $tenantId)
                        ->orWhereNull($table . '.tenant_id');
                });
            }

            $businessId = ActiveBusiness::id();

            if ($businessId && self::scopeHasColumn($table, 'business_id')) {
                $builder->where($table . '.business_id', $businessId);
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
        return $this->belongsTo(\App\Models\Business::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
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
