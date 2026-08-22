<?php

namespace Modules\Hospitality\Services;

use App\Support\ActiveBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HospitalityNumberService
{
    public function reservation(): string
    {
        return $this->next(\Modules\Hospitality\Models\Reservation::class, 'reservation_number', 'RSV', 5);
    }

    public function eventBooking(): string
    {
        return $this->next(\Modules\Hospitality\Models\EventBooking::class, 'booking_number', 'EVT', 5);
    }

    public function membership(): string
    {
        return $this->next(\Modules\Hospitality\Models\LoyaltyMember::class, 'membership_number', 'LOY', 5);
    }

    private function next(string $model, string $column, string $prefix, int $padding): string
    {
        $year = now()->format('Y');
        $base = "{$prefix}-{$year}-";

        if ($businessId = ActiveBusiness::id()) {
            DB::table('businesses')->where('id', $businessId)->lockForUpdate()->first();
        }

        /** @var class-string<Model> $model */
        $highest = $model::query()
            ->where($column, 'like', $base.'%')
            ->lockForUpdate()
            ->pluck($column)
            ->reduce(function (int $highest, string $number) use ($base) {
                if (! preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $number, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return $base.str_pad((string) ($highest + 1), $padding, '0', STR_PAD_LEFT);
    }
}
