<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailCustomerOffer;
use Modules\Retail\Models\RetailCustomerProfile;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailEnterpriseOperationsService;

class RetailCustomerController extends Controller
{
    public function index(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Customers',
            'section' => 'customers',
            'records' => $retail->customers()->latest()->paginate(20),
            'clients' => Client::orderBy('name')->get(),
            'offers' => Schema::hasTable('retail_customer_offers') ? RetailCustomerOffer::with('client', 'promotion')->latest()->limit(8)->get() : collect(),
        ]);
    }

    public function storeProfile(Request $request)
    {
        RetailCustomerProfile::updateOrCreate(
            ['client_id' => $request->validate(['client_id' => ['required', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())]])['client_id']],
            $request->validate([
                'customer_segment' => ['required', Rule::in(['Retail Customer', 'VIP Customer', 'Wholesale Customer', 'Corporate Customer'])],
                'shopping_preferences' => ['nullable', 'array'],
                'customer_notes' => ['nullable', 'string'],
            ])
        );

        return back()->with('status', 'Retail customer profile saved.');
    }

    public function storeOffer(Request $request, RetailEnterpriseOperationsService $enterprise)
    {
        $data = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_promotion_id' => ['nullable', Rule::exists('retail_promotions', 'id')->where('business_id', ActiveBusiness::id())],
            'offer_name' => ['nullable', 'string', 'max:255'],
            'offer_type' => ['nullable', 'string', 'max:100'],
            'segment' => ['nullable', 'string', 'max:100'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'status' => ['nullable', 'in:Draft,Active,Paused,Expired'],
        ]);

        $enterprise->createCustomerOffer(Client::findOrFail($data['client_id']), $data);

        return back()->with('status', 'Personalized retail offer generated from purchase behavior.');
    }
}
