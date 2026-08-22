<?php

namespace Modules\Hospitality\Services;

use App\Models\PosOrder;
use App\Models\PosOrderPayment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Services\DocumentService;
use App\Services\StockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\Ingredient;
use Modules\Hospitality\Models\MenuItemIngredient;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\RestaurantPurchase;
use Modules\Hospitality\Models\RestaurantTable;

class HospitalityRestaurantService
{
    public function __construct(private readonly DocumentService $documents)
    {
    }

    public function importMenu(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw ValidationException::withMessages(['menu_file' => 'The menu file could not be opened.']);
        }

        $headers = $this->headers(fgetcsv($handle) ?: []);
        $created = 0;
        $updated = 0;
        $skipped = [];
        $line = 1;

        DB::transaction(function () use ($handle, $headers, &$created, &$updated, &$skipped, &$line) {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (collect($row)->every(fn ($value) => trim((string) $value) === '')) {
                    continue;
                }

                $data = array_combine($headers, array_slice(array_pad($row, count($headers), ''), 0, count($headers)));
                $name = trim((string) ($data['name'] ?? $data['item'] ?? $data['menu_item'] ?? ''));
                $price = $this->money($data['price'] ?? $data['selling_price'] ?? $data['amount'] ?? null);

                if ($name === '' || $price === null) {
                    $skipped[] = "Line {$line}: missing item name or price.";
                    continue;
                }

                $categoryName = trim((string) ($data['category'] ?? 'Restaurant Menu')) ?: 'Restaurant Menu';
                $category = ProductCategory::firstOrCreate(
                    ['name' => $categoryName],
                    ['description' => 'Hospitality restaurant menu']
                );

                $sku = trim((string) ($data['sku'] ?? '')) ?: $this->sku($categoryName, $name);
                $product = Product::where('sku', $sku)->first();
                $wasExisting = (bool) $product;

                $product ??= new Product(['sku' => $sku]);
                $product->fill([
                    'product_category_id' => $category->id,
                    'name' => $name,
                    'description' => trim((string) ($data['description'] ?? $categoryName)),
                    'price' => $price,
                    'cost_price' => $this->money($data['cost_price'] ?? $data['cost'] ?? 0) ?? 0,
                    'stock_quantity' => max($this->money($data['stock'] ?? $data['quantity'] ?? 0) ?? 0, 0),
                    'stock_unit' => $this->stockUnit($data['stock_unit'] ?? $data['unit'] ?? $data['uom'] ?? null),
                    'is_active' => true,
                ])->save();

                $wasExisting ? $updated++ : $created++;
            }
        });

        fclose($handle);

        return compact('created', 'updated', 'skipped');
    }

    public function createFoodReservation(array $data): RestaurantOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $this->menuItems($data['items'] ?? []);
            $totals = $this->documents->totals($items);
            $guest = ! empty($data['guest_profile_id']) ? GuestProfile::find($data['guest_profile_id']) : null;
            $reservation = ! empty($data['reservation_id']) ? Reservation::with('guestProfile')->find($data['reservation_id']) : null;
            $guest ??= $reservation?->guestProfile;

            $posOrder = PosOrder::create([
                'client_id' => $guest?->client_id ?? $reservation?->client_id,
                'order_number' => $this->documents->number('pos_order'),
                'tracking_key' => Str::upper(Str::random(12)),
                'order_date' => now()->toDateString(),
                'customer_name' => $guest?->full_name,
                'customer_phone' => $guest?->phone,
                'customer_email' => $guest?->email,
                'customer_type' => 'Restaurant',
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'status' => $data['billing_status'] === 'Paid' ? 'paid' : 'pending',
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discountTotal'],
                'tax_total' => $totals['taxTotal'],
                'custom_amount' => 0,
                'total' => $totals['total'],
                'amount_paid' => $data['billing_status'] === 'Paid' ? $totals['total'] : 0,
                'notes' => 'Hospitality restaurant '.$data['order_type'],
            ]);

            foreach ($items as $item) {
                $posOrder->items()->create($item + ['line_total' => $this->documents->lineTotal($item)]);
                $this->consumeRecipe($item['product_id'], (float) $item['quantity']);
            }

            app(StockService::class)->syncSaleItems(
                collect(),
                collect($items),
                $posOrder,
                'Restaurant POS '.$posOrder->order_number
            );

            if ($data['billing_status'] === 'Paid' && ! empty($data['payment_method_id']) && class_exists(PosOrderPayment::class)) {
                $posOrder->payments()->create([
                    'payment_method_id' => $data['payment_method_id'],
                    'amount' => $totals['total'],
                    'payment_date' => now()->toDateString(),
                    'reference' => 'Restaurant POS',
                    'notes' => 'Hospitality restaurant payment.',
                ]);
            }

            $order = RestaurantOrder::create([
                'reservation_id' => $data['reservation_id'] ?? null,
                'guest_profile_id' => $guest?->id,
                'pos_order_id' => $posOrder->id,
                'restaurant_table_id' => $data['restaurant_table_id'] ?? null,
                'table_number' => $data['table_number'] ?? null,
                'reserved_for' => $data['reserved_for'] ?? null,
                'party_size' => $data['party_size'] ?? 1,
                'order_type' => $data['order_type'],
                'waiter_id' => $data['waiter_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'shipping_method' => $data['shipping_method'] ?? null,
                'kitchen_status' => $data['kitchen_status'] ?? 'Queued',
                'billing_status' => $data['billing_status'] ?? 'Open',
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['restaurant_table_id'])) {
                RestaurantTable::whereKey($data['restaurant_table_id'])->update([
                    'status' => $data['order_type'] === 'Table Reservation' ? 'Reserved' : 'Occupied',
                ]);
            }

            return $order->load('posOrder.items.product', 'guestProfile', 'reservation');
        });
    }

    public function updateProduction(RestaurantOrder $order, string $status): RestaurantOrder
    {
        $timestamps = match ($status) {
            'Preparing' => ['kitchen_started_at' => now()],
            'Ready' => ['kitchen_ready_at' => now()],
            'Served' => ['kitchen_served_at' => now()],
            default => [],
        };

        $order->update(['kitchen_status' => $status] + $timestamps);

        if ($status === 'Served' && $order->restaurant_table_id) {
            RestaurantTable::whereKey($order->restaurant_table_id)->update(['status' => 'Cleaning']);
        }

        return $order->refresh();
    }

    public function storePurchase(array $data): RestaurantPurchase
    {
        return DB::transaction(function () use ($data) {
            $items = collect($data['items'] ?? [])
                ->filter(fn ($item) => ! empty($item['description']) && (float) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one purchase item.']);
            }

            $supplier = ! empty($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;

            $purchase = RestaurantPurchase::create([
                'purchase_number' => $this->purchaseNumber(),
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplier?->name ?? $data['supplier_name'],
                'status' => $data['status'],
                'shipping_method' => $data['shipping_method'] ?? null,
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($items as $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_cost'];
                $total += $lineTotal;
                $purchase->items()->create([
                    'ingredient_id' => $item['ingredient_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);

                if ($data['status'] === 'Received' && ! empty($item['ingredient_id'])) {
                    Ingredient::whereKey($item['ingredient_id'])->increment('on_hand', (float) $item['quantity']);
                }
            }

            $purchase->update(['total' => $total]);

            return $purchase->load('items.ingredient');
        });
    }

    public function saveRecipe(int $productId, array $items): void
    {
        DB::transaction(function () use ($productId, $items) {
            MenuItemIngredient::where('product_id', $productId)->delete();

            foreach ($items as $item) {
                if (empty($item['ingredient_id']) || (float) ($item['quantity'] ?? 0) <= 0) {
                    continue;
                }

                MenuItemIngredient::create([
                    'product_id' => $productId,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        });
    }

    private function menuItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);

            if (! $productId || $quantity <= 0) {
                continue;
            }

            $product = Product::where('is_active', true)->find($productId);
            if (! $product) {
                continue;
            }

            $normalized[] = [
                'product_id' => $product->id,
                'title' => $product->name,
                'description' => $product->description ?: $product->name,
                'quantity' => $quantity,
                'unit_price' => (float) $product->price,
                'discount' => 0,
                'tax_rate' => (float) ($item['tax_rate'] ?? 0),
            ];
        }

        if (! $normalized) {
            throw ValidationException::withMessages(['items' => 'Choose at least one priced menu item.']);
        }

        return $normalized;
    }

    private function consumeRecipe(int $productId, float $menuQuantity): void
    {
        MenuItemIngredient::with('ingredient')
            ->where('product_id', $productId)
            ->get()
            ->each(function (MenuItemIngredient $recipe) use ($menuQuantity) {
                $recipe->ingredient?->decrement('on_hand', (float) $recipe->quantity * $menuQuantity);
            });
    }

    private function purchaseNumber(): string
    {
        $prefix = 'RPO-'.now()->format('Y').'-';
        $last = RestaurantPurchase::where('purchase_number', 'like', $prefix.'%')->max('id') ?? 0;

        return $prefix.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    private function headers(array $headers): array
    {
        return array_map(fn ($header) => Str::of((string) $header)->lower()->replace(['#', '/', '-'], ' ')->squish()->replace(' ', '_')->toString(), $headers);
    }

    private function money(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (float) preg_replace('/[^\d.\-]/', '', (string) $value);
    }

    private function stockUnit(mixed $value): string
    {
        $unit = Str::of((string) $value)->lower()->trim()->toString();

        return match ($unit) {
            'kg', 'kilogram', 'kilograms', 'kgs' => 'kg',
            'g', 'gram', 'grams', 'grammes', 'gm' => 'g',
            'l', 'litre', 'litres', 'liter', 'liters' => 'l',
            'ml', 'millilitre', 'millilitres', 'milliliter', 'milliliters' => 'ml',
            'box', 'boxes' => 'box',
            'carton', 'cartons' => 'carton',
            'pack', 'packs', 'packet', 'packets' => 'pack',
            'bottle', 'bottles' => 'bottle',
            'tray', 'trays' => 'tray',
            'bag', 'bags' => 'bag',
            default => 'pcs',
        };
    }

    private function sku(string $category, string $name): string
    {
        $base = 'MENU-'.Str::upper(Str::slug($category.'-'.$name, '-'));
        $sku = Str::limit($base, 90, '');
        $counter = 1;

        while (Product::where('sku', $sku)->exists()) {
            $counter++;
            $sku = Str::limit($base, 84, '').'-'.$counter;
        }

        return $sku;
    }
}
