<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailGiftCard;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailGiftCardService;

class RetailGiftCardController extends Controller
{
    public function index(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Gift Cards',
            'section' => 'gift-cards',
            'records' => $retail->giftCards()->paginate(20),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function issue(Request $request, RetailGiftCardService $cards)
    {
        $data = $request->validate([
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'size:3'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $cards->issue((float) $data['amount'], ! empty($data['client_id']) ? Client::find($data['client_id']) : null, $data);

        return back()->with('status', 'Gift card issued.');
    }

    public function recharge(Request $request, RetailGiftCard $giftCard, RetailGiftCardService $cards)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'reference' => ['nullable', 'string', 'max:255']]);
        $cards->recharge($giftCard, (float) $data['amount'], $data['reference'] ?? null);

        return back()->with('status', 'Gift card recharged.');
    }
}
