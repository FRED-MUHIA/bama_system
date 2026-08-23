<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\PosOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailCashDrawer;
use Modules\Retail\Models\RetailGiftCard;
use Modules\Retail\Models\RetailPromotion;
use Modules\Retail\Services\ProductIdentificationService;
use Modules\Retail\Services\RetailDashboardService;
use Modules\Retail\Services\RetailPosService;

class RetailPosController extends Controller
{
    public function index(Request $request, RetailDashboardService $dashboard, ProductIdentificationService $identifier)
    {
        $scanProduct = null;
        if ($request->filled('identifier')) {
            $scanProduct = $identifier->lookup($request->input('identifier_type', 'barcode'), $request->string('identifier')->toString());
        }

        return view('retail.pos', [
            'metrics' => $dashboard->metrics(),
            'topProducts' => $dashboard->topProducts(),
            'topCashiers' => $dashboard->topCashiers(),
            'recentOrders' => PosOrder::with('client', 'retailExtension.cashier')->latest()->limit(10)->get(),
            'products' => Product::with('retailProfile')->where('is_active', true)->orderBy('name')->limit(200)->get(),
            'clients' => Client::orderBy('name')->limit(200)->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'promotions' => RetailPromotion::where('status', 'Active')->orderBy('name')->get(),
            'giftCards' => RetailGiftCard::where('status', 'Active')->orderBy('card_number')->get(),
            'drawers' => RetailCashDrawer::where('status', 'Open')->latest()->get(),
            'scanProduct' => $scanProduct,
            'paymentTypes' => ['Cash', 'Card', 'Mobile Money', 'Bank Transfer', 'Wallet', 'Gift Card', 'Store Credit'],
            'saleTypes' => ['Sale', 'Quick Sale', 'Return', 'Exchange', 'Refund', 'Layaway'],
            'channels' => ['Store', 'Self Checkout', 'Mobile POS', 'Online Store'],
        ]);
    }

    public function store(Request $request, RetailPosService $pos)
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($item) => filled($item['product_id'] ?? null) || filled($item['description'] ?? null) || filled($item['unit_price'] ?? null))
                ->values()
                ->all(),
            'payments' => collect($request->input('payments', []))
                ->filter(fn ($payment) => filled($payment['amount'] ?? null))
                ->values()
                ->all(),
        ]);

        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cashier_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'retail_cash_drawer_id' => ['nullable', 'exists:retail_cash_drawers,id'],
            'retail_promotion_id' => ['nullable', 'exists:retail_promotions,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:100'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_type' => ['nullable', 'string', 'max:100'],
            'sale_type' => ['required', Rule::in(['Sale', 'Quick Sale', 'Return', 'Exchange', 'Refund', 'Layaway'])],
            'channel' => ['required', Rule::in(['Store', 'Self Checkout', 'Mobile POS', 'Online Store'])],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'layaway_due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'payments.*.method_type' => ['nullable', 'string', 'max:100'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'payments.*.retail_gift_card_id' => ['nullable', 'exists:retail_gift_cards,id'],
            'payments.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $order = $pos->createSale($data);

        return redirect()->route('retail.pos.index')->with('status', 'Retail POS sale '.$order->order_number.' saved.');
    }

    public function openDrawer(Request $request, RetailPosService $pos)
    {
        $pos->openDrawer($request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cashier_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'drawer_number' => ['required', 'string', 'max:100'],
            'opening_float' => ['nullable', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Cash drawer opened.');
    }

    public function closeDrawer(Request $request, RetailCashDrawer $drawer, RetailPosService $pos)
    {
        $pos->closeDrawer($drawer, $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Cash drawer closed.');
    }

    public function void(Request $request, PosOrder $posOrder, RetailPosService $pos)
    {
        $pos->void($posOrder, $request->input('reason'));

        return back()->with('status', 'POS transaction voided and stock restored.');
    }
}
