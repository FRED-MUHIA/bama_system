<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\RestaurantPurchase;
use Modules\Hospitality\Models\RestaurantTable;
use Modules\Hospitality\Services\HospitalityCrmService;
use Modules\Hospitality\Services\HospitalityRestaurantService;

class HospitalityFrontController extends Controller
{
    public function menu()
    {
        return view('hospitality.front', [
            'menuItems' => Product::with('category')->where('is_active', true)->orderBy('name')->get(),
            'restaurantTables' => RestaurantTable::whereIn('status', ['Available', 'Reserved'])->orderBy('section')->orderBy('table_number')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'shippingMethods' => RestaurantPurchase::SHIPPING_METHODS,
        ]);
    }

    public function reserve(Request $request, HospitalityRestaurantService $restaurant, HospitalityCrmService $crm)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:180'],
            'restaurant_table_id' => ['nullable', 'exists:hospitality_restaurant_tables,id'],
            'reserved_for' => ['required', 'date'],
            'party_size' => ['required', 'integer', 'min:1', 'max:100'],
            'order_type' => ['required', Rule::in(['Table Reservation', 'Dine In', 'Room Service', 'Takeaway'])],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'shipping_method' => ['nullable', Rule::in(RestaurantPurchase::SHIPPING_METHODS)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $guest = GuestProfile::create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'preferences' => [],
        ]);
        $crm->syncGuest($guest);

        $order = $restaurant->createFoodReservation([
            'guest_profile_id' => $guest->id,
            'restaurant_table_id' => $data['restaurant_table_id'] ?? null,
            'reserved_for' => $data['reserved_for'],
            'party_size' => $data['party_size'],
            'order_type' => $data['order_type'],
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'shipping_method' => $data['shipping_method'] ?? null,
            'kitchen_status' => 'Queued',
            'billing_status' => 'Open',
            'items' => $data['items'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'Reservation received. Reference '.$order->posOrder?->order_number.'.');
    }
}
