<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\URL;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\RestaurantTable;
use Modules\Hospitality\Models\Room;
use Modules\Hospitality\Services\HospitalityRestaurantService;

class HospitalityFrontController extends Controller
{
    public function menu()
    {
        return view('hospitality.front', [
            'menuItems' => Product::with('category')->where('is_active', true)->orderBy('name')->get(),
            'restaurantTables' => RestaurantTable::whereIn('status', ['Available', 'Reserved'])->orderBy('section')->orderBy('table_number')->get(),
            'staff' => $this->servingStaff()->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => auth()->check()
                ? PaymentMethod::where('is_active', true)->orderBy('name')->get()
                : collect(),
            'rooms' => Room::whereIn('status', ['Available', 'Occupied', 'Reserved'])->orderBy('room_number')->get(['id', 'room_number']),
            'recentOrders' => RestaurantOrder::with('posOrder.invoice.receipts.payment', 'waiter')
                ->whereIn('id', session('hospitality_order_ids', []))->latest()->limit(20)->get(),
        ]);
    }

    public function reserve(Request $request, HospitalityRestaurantService $restaurant)
    {
        $data = $request->validate([
            'restaurant_table_id' => ['nullable', 'exists:hospitality_restaurant_tables,id'],
            'waiter_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('id', $this->servingStaff()->select('users.id')->toBase()))],
            'order_type' => ['required', Rule::in(['Dine In', 'Takeaway', 'Room Service'])],
            'room_id' => ['nullable', 'required_if:order_type,Room Service', Rule::exists('hospitality_rooms', 'id')->where(fn ($query) => $query->whereIn('id', Room::whereIn('status', ['Available', 'Occupied', 'Reserved'])->select('id')->toBase()))],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = $restaurant->createFoodReservation([
            'restaurant_table_id' => empty($data['room_id']) ? ($data['restaurant_table_id'] ?? null) : null,
            'room_id' => $data['room_id'] ?? null,
            'waiter_id' => $data['waiter_id'],
            'order_type' => empty($data['room_id']) ? $data['order_type'] : 'Room Service',
            'kitchen_status' => 'Queued',
            'billing_status' => 'Open',
            'items' => $data['items'],
            'notes' => $data['notes'] ?? null,
        ]);

        session(['hospitality_order_ids' => collect(session('hospitality_order_ids', []))->push($order->id)->unique()->take(-20)->values()->all()]);

        return redirect(URL::signedRoute('public.hospitality.order', ['order' => $order->id]));
    }

    public function order(RestaurantOrder $order)
    {
        $order->load('posOrder.items', 'posOrder.invoice.receipts.payment', 'restaurantTable', 'room', 'waiter', 'business');

        return response()->view('hospitality.order', compact('order'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function servingStaff()
    {
        return User::where('is_active', true)->where('role', '!=', 'client_portal')
            ->whereIn('id', fn ($query) => $query->select('user_id')->from('business_user')
                ->where('business_id', ActiveBusiness::id() ?? 0)->where('status', 'Active'));
    }
}
