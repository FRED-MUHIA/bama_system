<?php

namespace Modules\RealEstate\Services;

use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\DB;

class RealEstateNumberService
{
    public function next(string $table, string $column, string $prefix): string
    {
        $year = now()->format('Y');
        $base = "{$prefix}-{$year}-";
        $highest = DB::table($table)
            ->where('business_id', ActiveBusiness::id())
            ->where($column, 'like', $base.'%')
            ->lockForUpdate()
            ->pluck($column)
            ->reduce(function (int $highest, string $number) use ($base) {
                if (! preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $number, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return $base.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
