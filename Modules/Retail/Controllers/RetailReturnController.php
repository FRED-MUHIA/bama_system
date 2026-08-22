<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PosOrder;
use Illuminate\Http\Request;
use Modules\Retail\Models\RetailReturnAuthorization;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailReturnService;

class RetailReturnController extends Controller
{
    public function index(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Returns & Refunds',
            'section' => 'returns',
            'records' => $retail->returns()->paginate(20),
            'orders' => PosOrder::with('items.product')->latest()->limit(50)->get(),
        ]);
    }

    public function store(Request $request, RetailReturnService $returns)
    {
        $data = $request->validate([
            'pos_order_id' => ['required', 'exists:pos_orders,id'],
            'return_type' => ['required', 'in:Return,Exchange,Refund,Defective Return'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_method' => ['required', 'in:Original Payment,Store Credit,Gift Card,Cash'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_order_item_id' => ['nullable', 'exists:pos_order_items,id'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.condition' => ['required', 'in:Resellable,Damaged,Defective,Opened'],
            'items.*.refund_amount' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $returns->authorize(PosOrder::with('items')->findOrFail($data['pos_order_id']), $data);

        return back()->with('status', 'Return authorization created.');
    }

    public function approve(RetailReturnAuthorization $returnAuthorization, RetailReturnService $returns)
    {
        $returns->approve($returnAuthorization);

        return back()->with('status', 'Return approved and stock restored when applicable.');
    }
}
