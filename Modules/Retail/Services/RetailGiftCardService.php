<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\PosOrder;
use App\Services\IamService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\RetailGiftCard;

class RetailGiftCardService
{
    public function __construct(private RetailNumberService $numbers)
    {
    }

    public function issue(float $amount, ?Client $client = null, array $data = []): RetailGiftCard
    {
        return DB::transaction(function () use ($amount, $client, $data) {
            $card = RetailGiftCard::create([
                'client_id' => $client?->id,
                'card_number' => $data['card_number'] ?? $this->numbers->giftCardNumber(),
                'issued_amount' => $amount,
                'balance' => $amount,
                'currency' => $data['currency'] ?? 'KES',
                'expires_at' => $data['expires_at'] ?? now()->addYear()->toDateString(),
                'status' => 'Active',
            ]);
            $card->transactions()->create(['type' => 'Issued', 'amount' => $amount, 'balance_after' => $amount, 'reference' => $data['reference'] ?? null]);
            app(IamService::class)->audit('retail.gift-card.issued', $card);

            return $card->load('client', 'transactions');
        });
    }

    public function recharge(RetailGiftCard $card, float $amount, ?string $reference = null): RetailGiftCard
    {
        return $this->changeBalance($card, abs($amount), 'Recharged', null, $reference);
    }

    public function redeem(RetailGiftCard $card, float $amount, ?PosOrder $order = null, ?string $reference = null): RetailGiftCard
    {
        if ((float) $card->balance < $amount) {
            throw ValidationException::withMessages(['amount' => 'Gift card balance is insufficient.']);
        }

        return $this->changeBalance($card, -abs($amount), 'Redeemed', $order, $reference);
    }

    private function changeBalance(RetailGiftCard $card, float $amount, string $type, ?PosOrder $order, ?string $reference): RetailGiftCard
    {
        return DB::transaction(function () use ($card, $amount, $type, $order, $reference) {
            $card = RetailGiftCard::whereKey($card->id)->lockForUpdate()->firstOrFail();
            $card->update(['balance' => max((float) $card->balance + $amount, 0)]);
            $card->transactions()->create(['pos_order_id' => $order?->id, 'type' => $type, 'amount' => abs($amount), 'balance_after' => $card->balance, 'reference' => $reference]);
            app(IamService::class)->audit('retail.gift-card.'.strtolower($type), $card);

            return $card->refresh()->load('transactions');
        });
    }
}
