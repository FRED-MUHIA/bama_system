<?php

namespace Modules\Agriculture\Services;

use App\Support\ActiveTenant;
use Illuminate\Support\Facades\DB;

class AgricultureNumberService
{
    public function next(string $table, string $column, string $prefix): string
    {
        $year = now()->format('Y');
        $base = "{$prefix}-{$year}-";

        $this->lockTenant();

        $highest = DB::table($table)
            ->where('tenant_id', ActiveTenant::id())
            ->where($column, 'like', $base.'%')
            ->lockForUpdate()
            ->pluck($column)
            ->reduce(function (int $highest, string $number) use ($base) {
                if (! preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $number, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return $base.str_pad((string) ($highest + 1), 5, '0', STR_PAD_LEFT);
    }

    private function lockTenant(): void
    {
        $tenantId = ActiveTenant::id();

        if ($tenantId) {
            DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->first();
        }
    }
}
