<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailCycleCount;
use Modules\Retail\Models\RetailReplenishmentPlan;
use Modules\Retail\Models\RetailWarehouse;
use Modules\Retail\Models\RetailWarehouseBin;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailEnterpriseOperationsService;
use Modules\Retail\Services\RetailInventoryService;

class RetailInventoryController extends Controller
{
    public function index(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Inventory Management',
            'section' => 'inventory',
            'records' => $retail->inventoryBalances()->paginate(20),
            'products' => Product::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'warehouses' => RetailWarehouse::orderBy('name')->get(),
            'bins' => RetailWarehouseBin::with('warehouse')->orderBy('bin_code')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'replenishmentPlans' => Schema::hasTable('retail_replenishment_plans') ? RetailReplenishmentPlan::with('product', 'supplier', 'purchaseOrder')->latest()->limit(8)->get() : collect(),
            'cycleCounts' => Schema::hasTable('retail_cycle_counts') ? RetailCycleCount::with('product', 'bin')->latest()->limit(8)->get() : collect(),
        ]);
    }

    public function adjust(Request $request, RetailInventoryService $inventory)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'quantity' => ['required', 'numeric'],
            'bucket' => ['required', Rule::in(['available_stock', 'reserved_stock', 'in_transit_stock', 'damaged_stock'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $inventory->adjust(Product::findOrFail($data['product_id']), (float) $data['quantity'], $data['bucket'], $data);

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
}
