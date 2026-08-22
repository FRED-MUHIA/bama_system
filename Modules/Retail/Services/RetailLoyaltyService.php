<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\PosOrder;
use App\Services\IamService;
use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\RetailLoyaltyAccount;

class RetailLoyaltyService
{
    public function __construct(private RetailNumberService $numbers)
    {
    }

    public function accountFor(Client $client): RetailLoyaltyAccount
    {
        return RetailLoyaltyAccount::firstOrCreate(
            ['client_id' => $client->id],
            ['loyalty_number' => $this->numbers->loyaltyNumber(), 'tier' => 'Bronze', 'joined_at' => now()]
        );
    }

    public function earn(Client $client, float $amount, ?PosOrder $order = null, string $reason = 'Sale'): RetailLoyaltyAccount
    {
        return DB::transaction(function () use ($client, $amount, $order, $reason) {
            $account = RetailLoyaltyAccount::where('client_id', $client->id)->lockForUpdate()->first() ?: $this->accountFor($client);
            $points = (int) floor(max($amount, 0));
            $account->increment('points_balance', $points);
            $account->increment('points_earned', $points);
            $account->update(['tier' => $this->tierFor((int) $account->points_earned + $points)]);
            $account->transactions()->create(['pos_order_id' => $order?->id, 'type' => 'Earned', 'points' => $points, 'amount' => $amount, 'reason' => $reason]);
            app(IamService::class)->audit('retail.loyalty.points.earned', $account);

            return $account->refresh();
        });
    }

    public function redeem(Client $client, int $points, ?PosOrder $order = null, string $reason = 'Reward redemption'): RetailLoyaltyAccount
    {
        return DB::transaction(function () use ($client, $points, $order, $reason) {
            $account = RetailLoyaltyAccount::where('client_id', $client->id)->lockForUpdate()->firstOrFail();
            $points = min($points, (int) $account->points_balance);
            $account->decrement('points_balance', $points);
            $account->increment('points_redeemed', $points);
            $account->transactions()->create(['pos_order_id' => $order?->id, 'type' => 'Redeemed', 'points' => -$points, 'amount' => $points, 'reason' => $reason]);
            app(IamService::class)->audit('retail.loyalty.points.redeemed', $account);

            return $account->refresh();
        });
    }

    private function tierFor(int $earned): string
    {
        return $earned >= 100000 ? 'Platinum' : ($earned >= 50000 ? 'Gold' : ($earned >= 10000 ? 'Silver' : 'Bronze'));
    }
}
