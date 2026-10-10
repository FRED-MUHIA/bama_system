<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\URL;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\RestaurantTable;
use Modules\Hospitality\Services\HospitalityRestaurantService;

class HospitalityFrontController extends Controller
{
    public function menu()
    {
        return view('hospitality.front', [
            'menuItems' => Product::with('category')->where('is_active', true)->orderBy('name')->get(),
            'restaurantTables' => RestaurantTable::whereIn('status', ['Available', 'Reserved'])->orderBy('section')->orderBy('table_number')->get(),
        ]);
    }

    public function reserve(Request $request, HospitalityRestaurantService $restaurant)
    {
        $data = $request->validate([
            'restaurant_table_id' => ['nullable', 'exists:hospitality_restaurant_tables,id'],
            'order_type' => ['required', Rule::in(['Dine In', 'Takeaway'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = $restaurant->createFoodReservation([
            'restaurant_table_id' => $data['restaurant_table_id'] ?? null,
            'order_type' => $data['order_type'],
            'kitchen_status' => 'Queued',
            'billing_status' => 'Open',
            'items' => $data['items'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect(URL::signedRoute('public.hospitality.order', ['order' => $order->id]));
    }

    public function order(RestaurantOrder $order)
    {
        $order->load('posOrder.items', 'restaurantTable');

        return response()->view('hospitality.order', compact('order'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
