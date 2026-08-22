<?php

namespace Modules\Fitness\Services;

use App\Support\ActiveBusiness;
use Illuminate\Support\Str;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MemberMembership;

class FitnessNumberService
{
    public function memberNumber(): string
    {
        return $this->next(Member::class, 'member_number', 'MEM');
    }

    public function membershipNumber(): string
    {
        return $this->next(MemberMembership::class, 'membership_number', 'GYM');
    }

    public function qrCode(): string
    {
        do {
            $code = 'FIT-'.Str::upper(Str::random(18));
        } while (Member::withoutGlobalScopes()->where('qr_code', $code)->exists());

        return $code;
    }

    private function next(string $model, string $column, string $prefix): string
    {
        $businessId = ActiveBusiness::id() ?: 0;
        $lastId = $model::withoutGlobalScopes()->where('business_id', $businessId)->max('id') ?? 0;

        return $prefix.'-'.now()->format('Y').'-'.str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }
}
