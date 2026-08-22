<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Controllers\Concerns\NormalizesHospitalityInput;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\Ingredient;
use Modules\Hospitality\Models\MenuItemIngredient;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\RestaurantPurchase;
use Modules\Hospitality\Models\RestaurantTable;
use Modules\Hospitality\Models\Unit;
use Modules\Hospitality\Services\HospitalityRestaurantService;

class RestaurantController extends Controller
{
    use NormalizesHospitalityInput;

    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Restaurant',
            'section' => 'restaurant',
            'records' => RestaurantOrder::with('reservation', 'posOrder.items.product', 'guestProfile', 'waiter', 'restaurantTable', 'paymentMethod')->latest()->paginate(30),
            'reservations' => Reservation::with('guestProfile')->whereIn('status', ['Confirmed', 'Checked In'])->latest()->limit(100)->get(),
            'guests' => GuestProfile::orderBy('full_name')->limit(200)->get(),
            'posOrders' => PosOrder::latest()->limit(100)->get(),
            'users' => User::orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'menuCategories' => ProductCategory::with(['products' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'menuItems' => Product::with('category')->where('is_active', true)->orderBy('name')->get(),
            'restaurantTables' => RestaurantTable::orderBy('section')->orderBy('table_number')->get(),
            'tableStatuses' => RestaurantTable::STATUSES,
            'units' => Unit::orderBy('name')->get(),
            'unitTypes' => Unit::TYPES,
            'ingredients' => Ingredient::with('unit')->orderBy('name')->get(),
            'recipes' => MenuItemIngredient::with('ingredient.unit')->get()->groupBy('product_id'),
            'purchases' => RestaurantPurchase::with('supplier', 'items.ingredient.unit')->latest()->limit(12)->get(),
            'purchaseStatuses' => RestaurantPurchase::STATUSES,
            'shippingMethods' => RestaurantPurchase::SHIPPING_METHODS,
            'orderTypes' => ['Table Reservation', 'Dine In', 'Room Service', 'Takeaway', 'Event Catering'],
            'kitchenStatuses' => ['Queued', 'Preparing', 'Ready', 'Served', 'Cancelled'],
            'billingStatuses' => ['Open', 'Room Charge', 'Paid', 'Cancelled'],
        ]);
    }

    public function store(Request $request, HospitalityRestaurantService $restaurant)
    {
        $data = $request->validate([
            'reservation_id' => ['nullable', 'exists:hospitality_reservations,id'],
            'guest_profile_id' => ['nullable', 'exists:hospitality_guest_profiles,id'],
            'restaurant_table_id' => ['nullable', 'exists:hospitality_restaurant_tables,id'],
            'table_number' => ['nullable', 'string', 'max:40'],
            'reserved_for' => ['nullable', 'date'],
            'party_size' => ['nullable', 'integer', 'min:1'],
            'order_type' => ['required', Rule::in(['Table Reservation', 'Dine In', 'Room Service', 'Takeaway', 'Event Catering'])],
            'waiter_id' => ['nullable', 'exists:users,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'shipping_method' => ['nullable', Rule::in(RestaurantPurchase::SHIPPING_METHODS)],
            'kitchen_status' => ['nullable', Rule::in(['Queued', 'Preparing', 'Ready', 'Served', 'Cancelled'])],
            'billing_status' => ['nullable', Rule::in(['Open', 'Room Charge', 'Paid', 'Cancelled'])],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = $this->zeroBlankNumbers($data, ['party_size']);
        $data['kitchen_status'] ??= 'Queued';
        $data['billing_status'] ??= 'Open';

        $order = $restaurant->createFoodReservation($data);

        return back()->with('status', 'Restaurant reservation created with POS order '.$order->posOrder?->order_number.'.');
    }

    public function storeTable(Request $request)
    {
        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:40'],
            'section' => ['nullable', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in(RestaurantTable::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        RestaurantTable::create($data);

        return back()->with('status', 'Restaurant table created.');
    }

    public function updateTableStatus(Request $request, RestaurantTable $table)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(RestaurantTable::STATUSES)],
        ]);

        $table->update($data);

        return back()->with('status', 'Restaurant table status updated.');
    }

    public function storeUnit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'max:20'],
            'type' => ['required', Rule::in(Unit::TYPES)],
        ]);

        Unit::create($data);

        return back()->with('status', 'Unit created.');
    }

    public function storeIngredient(Request $request)
    {
        $data = $request->validate([
            'unit_id' => ['nullable', 'exists:hospitality_units,id'],
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80'],
            'on_hand' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = $this->zeroBlankNumbers($data, ['on_hand', 'reorder_level', 'cost_per_unit']);
        Ingredient::create($data + ['is_active' => true]);

        return back()->with('status', 'Ingredient created.');
    }

    public function storeRecipe(Request $request, HospitalityRestaurantService $restaurant)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'items' => ['required', 'array'],
            'items.*.ingredient_id' => ['nullable', 'exists:hospitality_ingredients,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $restaurant->saveRecipe((int) $data['product_id'], $data['items']);

        return back()->with('status', 'Menu recipe saved.');
    }

    public function storePurchase(Request $request, HospitalityRestaurantService $restaurant)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('business_id', ActiveBusiness::id())],
            'supplier_name' => ['nullable', 'required_without:supplier_id', 'string', 'max:160'],
            'status' => ['required', Rule::in(RestaurantPurchase::STATUSES)],
            'shipping_method' => ['nullable', Rule::in(RestaurantPurchase::SHIPPING_METHODS)],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.ingredient_id' => ['nullable', 'exists:hospitality_ingredients,id'],
            'items.*.description' => ['nullable', 'string', 'max:160'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['items'] = collect($data['items'])
            ->map(function (array $item) {
                $item = $this->zeroBlankNumbers($item, ['quantity', 'unit_cost']);
                if (empty($item['description']) && ! empty($item['ingredient_id'])) {
                    $item['description'] = Ingredient::find($item['ingredient_id'])?->name;
                }

                return $item;
            })
            ->all();

        $purchase = $restaurant->storePurchase($data);

        return back()->with('status', 'Purchase '.$purchase->purchase_number.' saved.');
    }

    public function updateProduction(Request $request, RestaurantOrder $order, HospitalityRestaurantService $restaurant)
    {
        $data = $request->validate([
            'kitchen_status' => ['required', Rule::in(['Queued', 'Preparing', 'Ready', 'Served', 'Cancelled'])],
        ]);

        $restaurant->updateProduction($order, $data['kitchen_status']);

        return back()->with('status', 'Kitchen production updated.');
    }

    public function importMenu(Request $request, HospitalityRestaurantService $restaurant)
    {
        $data = $request->validate([
            'menu_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $restaurant->importMenu($data['menu_file']);
        $message = "Menu uploaded. Created {$result['created']} items and updated {$result['updated']} items.";
        if ($result['skipped']) {
            $message .= ' Skipped '.count($result['skipped']).' rows: '.implode(' ', array_slice($result['skipped'], 0, 3));
        }

        return back()->with('status', $message);
    }
}
