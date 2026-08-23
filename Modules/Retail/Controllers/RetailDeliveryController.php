<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Retail\Models\RetailDelivery;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Services\RetailOrderService;
use Modules\Retail\Services\RetailValidationRules;

class RetailDeliveryController extends Controller
{
    public function index()
    {
        return view('retail.module', [
            'title' => 'Delivery Management',
            'section' => 'deliveries',
            'records' => RetailDelivery::with('order.client', 'driver')->latest()->paginate(20),
            'orders' => RetailOrder::orderByDesc('id')->limit(50)->get(),
            'drivers' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, RetailOrderService $orders)
    {
        $data = $request->validate(RetailValidationRules::delivery());
        $order = RetailOrder::findOrFail($data['retail_order_id']);
        unset($data['retail_order_id']);
        $orders->scheduleDelivery($order, $data);

        return back()->with('status', 'Delivery scheduled.');
    }
}
