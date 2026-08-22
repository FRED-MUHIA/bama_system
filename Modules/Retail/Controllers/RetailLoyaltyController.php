<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailLoyaltyAccount;
use Modules\Retail\Services\RetailLoyaltyService;

class RetailLoyaltyController extends Controller
{
    public function index()
    {
        return view('retail.module', [
            'title' => 'Loyalty Programs',
            'section' => 'loyalty',
            'records' => RetailLoyaltyAccount::with('client')->latest()->paginate(20),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function enroll(Request $request, RetailLoyaltyService $loyalty)
    {
        $data = $request->validate(['client_id' => ['required', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())]]);
        $loyalty->accountFor(Client::findOrFail($data['client_id']));

        return back()->with('status', 'Customer enrolled in loyalty.');
    }
}
