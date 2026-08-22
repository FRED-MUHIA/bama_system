<?php

namespace App\Models\Concerns;

use App\Support\ActiveTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToTenant
{
    private static array $tenantScopeTableExists = [];
    private static array $tenantScopeColumnExists = [];

    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $model = $builder->getModel();
            $table = $model->getTable();

            if (! self::scopeHasColumn($table, 'tenant_id')) {
                return;
            }

            $tenantId = ActiveTenant::id();
            if ($tenantId) {
                $builder->where($table.'.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            $table = $model->getTable();
            if (self::scopeHasColumn($table, 'tenant_id') && empty($model->tenant_id)) {
                $model->tenant_id = ActiveTenant::id();
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    private static function scopeHasColumn(string $table, string $column): bool
    {
        if (! (self::$tenantScopeTableExists[$table] ??= Schema::hasTable($table))) {
            return false;
        }

        $key = $table.'.'.$column;

        return self::$tenantScopeColumnExists[$key] ??= Schema::hasColumn($table, $column);
    }
}
