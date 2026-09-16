<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\RetailCycleCount;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailProductVariant;
use Modules\Retail\Models\RetailReplenishmentPlan;
use Modules\Retail\Models\RetailWarehouse;
use Modules\Retail\Models\RetailWarehouseBin;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailEnterpriseOperationsService;
use Modules\Retail\Services\RetailInventoryService;

class RetailInventoryController extends Controller
{
    public function index(Request $request, RetailRepository $retail)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $search = trim($request->input('q', ''));

        return view('retail.module', [
            'title' => 'Inventory Management',
            'section' => 'inventory',
            'records' => $retail->inventoryBalances()->when($search !== '', function ($query) use ($search) {
                $like = "%{$search}%";
                $query->where(function ($query) use ($like) {
                    $query->whereHas('product', fn ($product) => $product->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like))
                        ->orWhereHas('variant', fn ($variant) => $variant->where('variant_name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like))
                        ->orWhereHas('branch', fn ($branch) => $branch->where('name', 'like', $like))
                        ->orWhereHas('warehouse', fn ($warehouse) => $warehouse->where('name', 'like', $like))
                        ->orWhereHas('bin', fn ($bin) => $bin->where('bin_code', 'like', $like));
                });
            })->orderBy('id')->paginate(20)->withQueryString(),
            'products' => Product::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'warehouses' => RetailWarehouse::orderBy('name')->get(),
            'bins' => RetailWarehouseBin::with('warehouse')->orderBy('bin_code')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'replenishmentPlans' => Schema::hasTable('retail_replenishment_plans') ? RetailReplenishmentPlan::with('product', 'supplier', 'purchaseOrder')->latest()->limit(8)->get() : collect(),
            'cycleCounts' => Schema::hasTable('retail_cycle_counts') ? RetailCycleCount::with('product', 'bin')->latest()->limit(8)->get() : collect(),
            'stockMovements' => Schema::hasTable('stock_movements')
                ? StockMovement::with('product')->latest()->limit(30)->get()
                : collect(),
        ]);
    }

    public function adjust(Request $request, RetailInventoryService $inventory)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_product_variant_id' => ['nullable', Rule::exists('retail_product_variants', 'id')->where('business_id', ActiveBusiness::id())],
            'quantity' => ['required', 'numeric'],
            'movement' => ['nullable', Rule::in(['Add', 'Remove', 'Set'])],
            'bucket' => ['required', Rule::in(['available_stock', 'reserved_stock', 'in_transit_stock', 'damaged_stock'])],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_id' => ['nullable', Rule::exists('retail_warehouses', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_bin_id' => ['nullable', Rule::exists('retail_warehouse_bins', 'id')->where('business_id', ActiveBusiness::id())],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $context = $this->inventoryContext($data);
        $quantity = $this->movementQuantity($product, (float) $data['quantity'], $data, $context);

        if (! empty($data['retail_product_variant_id'])) {
            $variant = RetailProductVariant::findOrFail($data['retail_product_variant_id']);
            if ((int) $variant->parent_product_id !== (int) $product->id) {
                throw ValidationException::withMessages([
                    'retail_product_variant_id' => 'The selected variant does not belong to the selected product.',
                ]);
            }

            $inventory->adjustVariant($variant, $quantity, $data['bucket'], $context);
        } else {
            $inventory->adjust($product, $quantity, $data['bucket'], $context);
        }

        return back()->with('status', 'Retail inventory adjusted.');
    }

    public function reserve(Request $request, RetailInventoryService $inventory)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $inventory->reserve(Product::findOrFail($data['product_id']), (float) $data['quantity'], $data);

        return back()->with('status', 'Retail inventory reserved.');
    }

    public function transfer(Request $request, RetailInventoryService $inventory)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'from_branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'to_branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $inventory->transfer(
            Product::findOrFail($data['product_id']),
            (float) $data['quantity'],
            ['branch_id' => $data['from_branch_id'] ?? null, 'reference' => $data['reference'] ?? 'Branch transfer out', 'notes' => $data['notes'] ?? null],
            ['branch_id' => $data['to_branch_id'] ?? null, 'reference' => $data['reference'] ?? 'Branch transfer in', 'notes' => $data['notes'] ?? null]
        );

        return back()->with('status', 'Retail stock transfer recorded.');
    }

    public function replenish(Request $request, RetailEnterpriseOperationsService $enterprise)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_id' => ['nullable', Rule::exists('retail_warehouses', 'id')->where('business_id', ActiveBusiness::id())],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('business_id', ActiveBusiness::id())],
            'forecast_period_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'safety_stock_factor' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'landed_cost_components' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $enterprise->createReplenishmentPlan(Product::findOrFail($data['product_id']), $data);

        return back()->with('status', 'Replenishment forecast and safety stock plan generated.');
    }

    public function purchaseOrder(RetailReplenishmentPlan $plan, RetailEnterpriseOperationsService $enterprise)
    {
        $purchaseOrder = $enterprise->generatePurchaseOrder($plan);

        return back()->with('status', $purchaseOrder ? 'Shared purchase order drafted from retail replenishment.' : 'Plan does not need a purchase order yet.');
    }

    public function cycleCount(Request $request, RetailEnterpriseOperationsService $enterprise)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_id' => ['nullable', Rule::exists('retail_warehouses', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_bin_id' => ['nullable', Rule::exists('retail_warehouse_bins', 'id')->where('business_id', ActiveBusiness::id())],
            'counted_quantity' => ['required', 'numeric', 'min:0'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $enterprise->recordCycleCount(Product::findOrFail($data['product_id']), $data);

        return back()->with('status', 'Cycle count recorded and variance posted.');
    }

    private function inventoryContext(array $data): array
    {
        return collect($data)
            ->only(['retail_product_variant_id', 'branch_id', 'retail_warehouse_id', 'retail_warehouse_bin_id', 'reference', 'notes'])
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    private function movementQuantity(Product $product, float $quantity, array $data, array $context): float
    {
        return match ($data['movement'] ?? null) {
            'Add' => abs($quantity),
            'Remove' => -abs($quantity),
            'Set' => $this->setMovementQuantity($product, $quantity, $data['bucket'], $context),
            default => $quantity,
        };
    }

    private function setMovementQuantity(Product $product, float $quantity, string $bucket, array $context): float
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages(['quantity' => 'Stock cannot be set below zero.']);
        }

        $current = (float) (RetailInventoryBalance::query()
            ->where([
                'product_id' => $product->id,
                'retail_product_variant_id' => $context['retail_product_variant_id'] ?? null,
                'branch_id' => $context['branch_id'] ?? null,
                'retail_warehouse_id' => $context['retail_warehouse_id'] ?? null,
                'retail_warehouse_bin_id' => $context['retail_warehouse_bin_id'] ?? null,
            ])
            ->value($bucket) ?? 0);

        return $quantity - $current;
    }
}
