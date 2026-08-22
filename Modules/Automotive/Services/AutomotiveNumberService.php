<?php

namespace Modules\Automotive\Services;

use App\Support\ActiveTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AutomotiveNumberService
{
    public function next(string $prefix, string $modelClass, string $column): string
    {
        $year = now()->format('Y');
        $base = "{$prefix}-{$year}-";

        /** @var class-string<Model> $modelClass */
        $latest = $modelClass::withoutGlobalScopes()
            ->where('tenant_id', ActiveTenant::id())
            ->where($column, 'like', "{$base}%")
            ->orderByDesc($column)
            ->value($column);

        $sequence = 1;
        if (is_string($latest) && preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%05d', $base, $sequence);
    }

    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }
}
