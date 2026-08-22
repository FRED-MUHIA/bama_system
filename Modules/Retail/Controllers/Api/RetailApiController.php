<?php

namespace Modules\Retail\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Modules\Retail\Models\RetailGiftCard;
use Modules\Retail\Models\RetailPromotion;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailGiftCardService;
use Modules\Retail\Services\RetailLoyaltyService;
use Modules\Retail\Services\RetailOrderService;
use Modules\Retail\Services\RetailPromotionService;
use Modules\Retail\Services\RetailValidationRules;

class RetailApiController extends Controller
{
    public function dashboard(\Modules\Retail\Services\RetailDashboardService $dashboard)
    {
        return response()->json(['data' => $dashboard->metrics()]);
    }

    public function products(Request $request, RetailRepository $retail)
    {
        return response()->json(['data' => $retail->productSearch($request->query('q'))->limit(50)->get()]);
    }

    public function promotions(RetailPromotionService $promotions)
    {
        return response()->json(['data' => $promotions->activeFor()]);
    }

    public function createPromotion(Request $request)
    {
        return response()->json(['data' => RetailPromotion::create($request->validate(RetailValidationRules::promotion()))], 201);
    }

    public function createOrder(Request $request, RetailOrderService $orders)
    {
        return response()->json(['data' => $orders->create($request->validate(RetailValidationRules::order()))], 201);
    }

    public function loyalty(Client $client, RetailLoyaltyService $loyalty)
    {
        return response()->json(['data' => $loyalty->accountFor($client)->load('transactions')]);
    }

    public function giftCardBalance(RetailGiftCard $giftCard)
    {
        return response()->json(['data' => ['card_number' => $giftCard->card_number, 'balance' => $giftCard->balance, 'status' => $giftCard->status]]);
    }

    public function issueGiftCard(Request $request, RetailGiftCardService $cards)
    {
        $data = $request->validate(['client_id' => ['nullable', 'exists:clients,id'], 'amount' => ['required', 'numeric', 'min:0.01'], 'currency' => ['required', 'size:3'], 'expires_at' => ['nullable', 'date']]);

        return response()->json(['data' => $cards->issue((float) $data['amount'], ! empty($data['client_id']) ? Client::find($data['client_id']) : null, $data)], 201);
    }
}
