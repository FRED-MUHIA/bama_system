<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailOrderFulfillment;
use Modules\Retail\Models\RetailWarehouse;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailEnterpriseOperationsService;
use Modules\Retail\Services\RetailOrderService;
use Modules\Retail\Services\RetailValidationRules;

class RetailOrderController extends Controller
{
    public function index(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Order Management',
            'section' => 'orders',
            'records' => $retail->orders()->paginate(20),
            'clients' => Client::orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => RetailWarehouse::where('status', 'Active')->orderBy('name')->get(),
            'fulfillments' => Schema::hasTable('retail_order_fulfillments') ? RetailOrderFulfillment::with('order', 'branch', 'warehouse')->latest()->limit(8)->get() : collect(),
        ]);
    }

    public function store(Request $request, RetailOrderService $orders)
    {
        $orders->create($request->validate(RetailValidationRules::order()));

        return back()->with('status', 'Retail order saved.');
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:company,individual'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('clients', 'email')->where('business_id', ActiveBusiness::id())],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'customer_segment' => ['nullable', Rule::in(['Retail Customer', 'VIP Customer', 'Wholesale Customer', 'Corporate Customer'])],
        ]);

        $segment = $data['customer_segment'] ?? 'Retail Customer';
        unset($data['customer_segment']);

        $client = Client::create($data + ['type' => $data['type'] ?? 'individual']);
        $client->retailProfile()->create(['customer_segment' => $segment]);

        return back()->with('status', 'Customer added and ready for orders.')->with('selectedCustomerId', $client->id);
    }

    public function routeFulfillment(Request $request, RetailOrder $order, RetailEnterpriseOperationsService $enterprise)
    {
        $enterprise->routeFulfillment($order, $request->validate([
            'fulfillment_type' => ['required', Rule::in(['BOPIS', 'Ship From Store', 'Home Delivery', 'Marketplace Fulfillment'])],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_id' => ['nullable', Rule::exists('retail_warehouses', 'id')->where('business_id', ActiveBusiness::id())],
            'routing_status' => ['nullable', Rule::in(['Routed', 'Picking', 'Packed', 'Ready For Pickup', 'Shipped', 'Completed'])],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Omnichannel fulfillment route saved.');
    }
}
