<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailEcommerceIntegration;
use Modules\Retail\Models\RetailReplenishmentPlan;
use Modules\Retail\Models\RetailSupplierProfile;
use Modules\Retail\Models\RetailSupplierContract;
use Modules\Retail\Models\RetailTaxJurisdiction;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailEnterpriseOperationsService;

class RetailOperationsController extends Controller
{
    public function pos()
    {
        return redirect()->route('pos-orders.create');
    }

    public function procurement()
    {
        return view('retail.module', [
            'title' => 'Retail Procurement',
            'section' => 'procurement',
            'records' => Schema::hasTable('retail_replenishment_plans') ? RetailReplenishmentPlan::with('product', 'supplier', 'purchaseOrder')->latest()->paginate(20) : collect(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::with('supplier')->latest()->limit(10)->get(),
            'contracts' => Schema::hasTable('retail_supplier_contracts') ? RetailSupplierContract::with('supplier', 'product')->latest()->limit(10)->get() : collect(),
        ]);
    }

    public function suppliers(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Suppliers',
            'section' => 'suppliers',
            'records' => $retail->suppliers()->paginate(20),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'contracts' => Schema::hasTable('retail_supplier_contracts') ? RetailSupplierContract::with('supplier', 'product')->latest()->limit(10)->get() : collect(),
        ]);
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'kra_pin' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'supplier_code' => ['nullable', 'string', 'max:100'],
            'tax_information' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'delivery_accuracy' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ]);

        $supplier = Supplier::create(collect($data)->only(['name', 'email', 'phone', 'kra_pin', 'address'])->all());
        RetailSupplierProfile::create(collect($data)->except(['name', 'email', 'phone', 'kra_pin', 'address'])->all() + ['supplier_id' => $supplier->id]);

        return back()->with('status', 'Retail supplier saved.');
    }

    public function storeSupplierContract(Request $request, Supplier $supplier, RetailEnterpriseOperationsService $enterprise)
    {
        $enterprise->storeSupplierContract($supplier, $request->validate([
            'product_id' => ['nullable', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'contract_number' => ['required', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'service_level_agreement' => ['nullable', 'string', 'max:255'],
            'landed_cost_components' => ['nullable', 'array'],
            'status' => ['nullable', 'in:Draft,Active,Expired,Terminated'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Supplier contract, scorecard, lead time, and landed cost log saved.');
    }

    public function branches()
    {
        return view('retail.module', ['title' => 'Branches', 'section' => 'branches', 'records' => Branch::latest()->paginate(20)]);
    }

    public function storeBranch(Request $request)
    {
        Branch::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')->where('business_id', ActiveBusiness::id())],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Retail branch saved.');
    }

    public function ecommerce()
    {
        return view('retail.module', ['title' => 'Ecommerce Integration', 'section' => 'ecommerce', 'records' => RetailEcommerceIntegration::latest()->paginate(20)]);
    }

    public function storeEcommerce(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'max:100'],
            'external_store_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Draft,Active,Paused,Disconnected'],
            'website_url' => ['nullable', 'url', 'max:255'],
        ]);
        $websiteUrl = $data['website_url'] ?? null;
        unset($data['website_url']);

        RetailEcommerceIntegration::create($data + ['settings' => [
            'product_sync' => $request->boolean('product_sync'),
            'inventory_sync' => $request->boolean('inventory_sync'),
            'order_sync' => $request->boolean('order_sync'),
            'customer_sync' => $request->boolean('customer_sync'),
            'website_url' => $websiteUrl,
            'api_key' => Str::random(48),
        ]]);

        return back()->with('status', 'Ecommerce channel saved. Product, category, and pricing feed keys are ready.');
    }

    public function syncEcommerce(RetailEcommerceIntegration $integration)
    {
        $settings = $integration->settings ?: [];
        $settings['api_key'] ??= Str::random(48);

        $integration->update([
            'last_product_sync_at' => now(),
            'last_inventory_sync_at' => now(),
            'last_order_sync_at' => now(),
            'last_customer_sync_at' => now(),
            'settings' => $settings,
            'status' => 'Active',
        ]);

        return back()->with('status', 'Ecommerce sync markers updated.');
    }

    public function analytics()
    {
        return view('retail.analytics', [
            'service' => app(\Modules\Retail\Services\RetailDashboardService::class),
            'enterprise' => app(RetailEnterpriseOperationsService::class),
        ]);
    }

    public function reports()
    {
        return view('retail.reports', [
            'records' => Supplier::with('retailProfile')->latest()->limit(10)->get(),
            'enterprise' => app(RetailEnterpriseOperationsService::class),
            'taxJurisdictions' => Schema::hasTable('retail_tax_jurisdictions') ? RetailTaxJurisdiction::latest()->limit(10)->get() : collect(),
        ]);
    }

    public function settings()
    {
        return view('retail.settings', [
            'taxJurisdictions' => Schema::hasTable('retail_tax_jurisdictions') ? RetailTaxJurisdiction::latest()->get() : collect(),
        ]);
    }

    public function storeTaxJurisdiction(Request $request, RetailEnterpriseOperationsService $enterprise)
    {
        $enterprise->storeTaxJurisdiction($request->validate([
            'country' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'tax_name' => ['required', 'string', 'max:100'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
            'status' => ['required', 'in:Active,Inactive'],
        ]));

        return back()->with('status', 'Retail tax jurisdiction and currency mapping saved.');
    }
}
