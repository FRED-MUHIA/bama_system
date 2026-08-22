<?php

namespace Modules\Retail\Services;

use Illuminate\Support\Str;
use Modules\Retail\Models\RetailGiftCard;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailReturnAuthorization;

class RetailNumberService
{
    public function loyaltyNumber(): string
    {
        return $this->next('LOY', \Modules\Retail\Models\RetailLoyaltyAccount::class, 'loyalty_number');
    }

    public function returnNumber(): string
    {
        return $this->next('RMA', RetailReturnAuthorization::class, 'return_number');
    }

    public function giftCardNumber(): string
    {
        do {
            $number = 'GC-'.now()->format('ym').'-'.Str::upper(Str::random(8));
        } while (RetailGiftCard::where('card_number', $number)->exists());

        return $number;
    }

    public function orderNumber(): string
    {
        return $this->next('RO', RetailOrder::class, 'order_number');
    }

    private function next(string $prefix, string $model, string $column): string
    {
        $latest = (int) $model::withoutGlobalScopes()->where($column, 'like', $prefix.'-'.now()->format('Y').'%')->max('id');

        return $prefix.'-'.now()->format('Y').'-'.str_pad((string) ($latest + 1), 6, '0', STR_PAD_LEFT);
    }
}
