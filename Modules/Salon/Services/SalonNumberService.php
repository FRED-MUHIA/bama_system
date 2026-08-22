<?php

namespace Modules\Salon\Services;

use App\Support\ActiveTenant;
use Illuminate\Support\Facades\DB;

class SalonNumberService
{
    public function appointment(): string
    {
        return $this->number('salon_appointments', 'appointment_number', 'APT');
    }

    public function client(): string
    {
        return $this->number('salon_client_profiles', 'client_code', 'SCL');
    }

    public function membership(): string
    {
        return $this->number('salon_memberships', 'membership_number', 'SMB');
    }

    public function giftCard(): string
    {
        return $this->number('salon_gift_cards', 'card_number', 'SGC');
    }

    private function number(string $table, string $column, string $prefix): string
    {
        $tenant = ActiveTenant::id() ?: 0;
        $date = now()->format('ymd');
        $count = DB::table($table)
            ->where('tenant_id', $tenant ?: null)
            ->where($column, 'like', "{$prefix}-{$date}-%")
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
