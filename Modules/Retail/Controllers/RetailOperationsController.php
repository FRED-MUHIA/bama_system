<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Branch;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Client;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailEcommerceIntegration;
use Modules\Retail\Models\RetailCustomerOffer;
use Modules\Retail\Models\RetailCustomerProfile;
use Modules\Retail\Models\RetailCycleCount;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailLoyaltyAccount;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailOrderFulfillment;
use Modules\Retail\Models\RetailReplenishmentPlan;
use Modules\Retail\Models\RetailReturnAuthorization;
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

        DB::transaction(function () use ($data) {
            $supplier = Supplier::create(collect($data)->only(['name', 'email', 'phone', 'kra_pin', 'address'])->all());
            $profileData = collect($data)->except(['name', 'email', 'phone', 'kra_pin', 'address'])->all();
            $profileData['lead_time_days'] = (int) ($profileData['lead_time_days'] ?? 0);
            $profileData['delivery_accuracy'] = (float) ($profileData['delivery_accuracy'] ?? 0);
            $profileData['rating'] = (float) ($profileData['rating'] ?? 0);

            RetailSupplierProfile::create($profileData + ['supplier_id' => $supplier->id]);
        });

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
        return view('retail.module', ['title' => 'Website Catalog', 'section' => 'ecommerce', 'records' => RetailEcommerceIntegration::latest()->paginate(20)]);
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

        return back()->with('status', 'Website catalog saved. Product, category, and pricing feed keys are ready.');
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

        return back()->with('status', 'Website catalog sync markers updated.');
    }

    public function analytics()
    {
        return view('retail.analytics', [
            'service' => app(\Modules\Retail\Services\RetailDashboardService::class),
            'enterprise' => app(RetailEnterpriseOperationsService::class),
        ]);
    }

    public function reports(Request $request)
    {
        $catalog = $this->retailReportCatalog();
        $reportOptions = collect($catalog['primary'])->merge($catalog['advanced']);
        $selectedReport = $request->query('report', 'daily-sales');

        if (! $reportOptions->contains('slug', $selectedReport)) {
            $selectedReport = 'daily-sales';
        }

        $enterprise = app(RetailEnterpriseOperationsService::class);

        return view('retail.reports', [
            'records' => Supplier::with('retailProfile')->latest()->limit(10)->get(),
            'primaryReports' => $catalog['primary'],
            'advancedReports' => $catalog['advanced'],
            'selectedReport' => $selectedReport,
            'selectedReportMeta' => $reportOptions->firstWhere('slug', $selectedReport),
            'report' => $this->retailReportData($selectedReport, $enterprise),
            'enterprise' => $enterprise,
            'taxJurisdictions' => Schema::hasTable('retail_tax_jurisdictions') ? RetailTaxJurisdiction::latest()->limit(10)->get() : collect(),
        ]);
    }

    public function exportReport(Request $request, string $type, ?string $format = null)
    {
        $catalog = $this->retailReportCatalog();
        $reportOptions = collect($catalog['primary'])->merge($catalog['advanced']);
        $selectedReport = $reportOptions->contains('slug', $type) ? $type : 'daily-sales';
        $downloadFormat = strtolower((string) ($format ?? $request->query('format', 'pdf')));
        $downloadFormat = in_array($downloadFormat, ['excel', 'xls', 'xlsx'], true) ? 'xls' : 'pdf';

        $report = $this->retailReportData($selectedReport, app(RetailEnterpriseOperationsService::class));
        $rows = collect($report['rows'] ?? []);
        $columns = $report['columns'] ?? [];
        $filename = Str::slug($report['title'] ?? 'retail-report').'-'.now()->format('YmdHis').'.'.$downloadFormat;

        if ($downloadFormat === 'pdf') {
            $html = view('retail.reports.export', [
                'title' => $report['title'] ?? 'Retail Report',
                'subtitle' => $report['subtitle'] ?? 'Retail report exported from the active account.',
                'columns' => $columns,
                'rows' => $rows,
            ])->render();

            return Pdf::loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->download($filename);
        }

        $columnNames = array_values($columns);

        return response()->streamDownload(function () use ($columnNames, $rows, $columns) {
            $out = fopen('php://output', 'wb');
            fputcsv($out, $columnNames, "\t");

            foreach ($rows as $row) {
                $line = [];
                foreach (array_keys($columns) as $key) {
                    $line[] = $row[$key] ?? '';
                }
                fputcsv($out, $line, "\t");
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel']);
    }

    private function retailReportCatalog(): array
    {
        return [
            'primary' => [
                ['slug' => 'daily-sales', 'label' => 'Daily Sales', 'icon' => 'bi-file-earmark-bar-graph', 'tone' => 'success'],
                ['slug' => 'product-sales', 'label' => 'Product Sales', 'icon' => 'bi-file-earmark-bar-graph', 'tone' => 'primary'],
                ['slug' => 'stock-levels', 'label' => 'Stock Levels', 'icon' => 'bi-file-earmark-bar-graph', 'tone' => 'success'],
                ['slug' => 'returns', 'label' => 'Returns', 'icon' => 'bi-file-earmark-bar-graph', 'tone' => 'primary'],
            ],
            'advanced' => [
                ['slug' => 'monthly-sales', 'label' => 'Monthly Sales', 'icon' => 'bi-calendar3', 'tone' => 'primary'],
                ['slug' => 'cashier-sales', 'label' => 'Cashier Sales', 'icon' => 'bi-person-badge', 'tone' => 'primary'],
                ['slug' => 'reorder-reports', 'label' => 'Reorder Reports', 'icon' => 'bi-cart-plus', 'tone' => 'success'],
                ['slug' => 'safety-stock-forecast', 'label' => 'Safety Stock Forecast', 'icon' => 'bi-shield-check', 'tone' => 'success'],
                ['slug' => 'cycle-count-variance', 'label' => 'Cycle Count Variance', 'icon' => 'bi-clipboard-data', 'tone' => 'primary'],
                ['slug' => 'valuation-reports', 'label' => 'Valuation Reports', 'icon' => 'bi-cash-stack', 'tone' => 'success'],
                ['slug' => 'loyalty-reports', 'label' => 'Loyalty Reports', 'icon' => 'bi-gem', 'tone' => 'primary'],
                ['slug' => 'purchase-history', 'label' => 'Purchase History', 'icon' => 'bi-clock-history', 'tone' => 'primary'],
                ['slug' => 'customer-segments', 'label' => 'Customer Segments', 'icon' => 'bi-people', 'tone' => 'success'],
                ['slug' => 'personalized-offers', 'label' => 'Personalized Offers', 'icon' => 'bi-bullseye', 'tone' => 'primary'],
                ['slug' => 'supplier-performance', 'label' => 'Supplier Performance', 'icon' => 'bi-truck', 'tone' => 'success'],
                ['slug' => 'supplier-contracts', 'label' => 'Supplier Contracts', 'icon' => 'bi-file-earmark-check', 'tone' => 'primary'],
                ['slug' => 'purchase-reports', 'label' => 'Purchase Reports', 'icon' => 'bi-bag-check', 'tone' => 'primary'],
                ['slug' => 'landed-cost', 'label' => 'Landed Cost', 'icon' => 'bi-calculator', 'tone' => 'success'],
                ['slug' => 'rma-restocking', 'label' => 'RMA Restocking', 'icon' => 'bi-arrow-counterclockwise', 'tone' => 'primary'],
                ['slug' => 'bopis-fulfillment', 'label' => 'BOPIS Fulfillment', 'icon' => 'bi-shop-window', 'tone' => 'success'],
                ['slug' => 'ship-from-store', 'label' => 'Ship From Store', 'icon' => 'bi-truck', 'tone' => 'primary'],
                ['slug' => 'branch-revenue', 'label' => 'Branch Revenue', 'icon' => 'bi-diagram-3', 'tone' => 'success'],
                ['slug' => 'branch-profitability', 'label' => 'Branch Profitability', 'icon' => 'bi-graph-up-arrow', 'tone' => 'primary'],
                ['slug' => 'branch-inventory', 'label' => 'Branch Inventory', 'icon' => 'bi-boxes', 'tone' => 'success'],
                ['slug' => 'sku-profitability', 'label' => 'SKU Profitability', 'icon' => 'bi-upc', 'tone' => 'primary'],
                ['slug' => 'vat-gst-jurisdictions', 'label' => 'VAT/GST Jurisdictions', 'icon' => 'bi-receipt', 'tone' => 'success'],
                ['slug' => 'audit-reporting', 'label' => 'Audit Reporting', 'icon' => 'bi-shield-lock', 'tone' => 'primary'],
            ],
        ];
    }

    private function retailReportData(string $slug, RetailEnterpriseOperationsService $enterprise): array
    {
        return match ($slug) {
            'product-sales' => $this->productSalesReport(),
            'stock-levels' => $this->stockLevelsReport(),
            'returns' => $this->returnsReport(),
            'monthly-sales' => $this->monthlySalesReport(),
            'cashier-sales' => $this->cashierSalesReport(),
            'reorder-reports' => $this->replenishmentReport('Reorder Reports', 'Products with proposed replenishment quantities and draft purchase order links.'),
            'safety-stock-forecast' => $this->replenishmentReport('Safety Stock Forecast', 'Forecast demand, safety stock, reorder points, and recommended order quantities.'),
            'cycle-count-variance' => $this->cycleCountVarianceReport(),
            'valuation-reports' => $this->valuationReport(),
            'loyalty-reports' => $this->loyaltyReport(),
            'purchase-history' => $this->purchaseHistoryReport(),
            'customer-segments' => $this->customerSegmentsReport(),
            'personalized-offers' => $this->personalizedOffersReport(),
            'supplier-performance' => $this->supplierPerformanceReport(),
            'supplier-contracts' => $this->supplierContractsReport(),
            'purchase-reports' => $this->purchaseReports(),
            'landed-cost' => $this->landedCostReport(),
            'rma-restocking' => $this->rmaRestockingReport(),
            'bopis-fulfillment' => $this->fulfillmentReport('BOPIS', 'BOPIS Fulfillment', 'Buy online, pick up in store routing status.'),
            'ship-from-store' => $this->fulfillmentReport('Ship From Store', 'Ship From Store', 'Store-origin shipment routing and carrier tracking.'),
            'branch-revenue' => $this->branchRevenueReport(),
            'branch-profitability' => $this->branchProfitabilityReport(),
            'branch-inventory' => $this->branchInventoryReport(),
            'sku-profitability' => $this->skuProfitabilityReport($enterprise),
            'vat-gst-jurisdictions' => $this->vatJurisdictionReport(),
            'audit-reporting' => $this->auditReport(),
            default => $this->dailySalesReport(),
        };
    }

    private function dailySalesReport(): array
    {
        $orders = PosOrder::where('status', '!=', 'cancelled')->latest('order_date')->limit(500)->get();
        $rows = $orders
            ->groupBy(fn ($order) => optional($order->order_date)->format('Y-m-d') ?: 'No date')
            ->map(fn ($group, $date) => [
                'date' => $date,
                'orders' => number_format($group->count()),
                'sales' => $this->money($group->sum('total')),
                'paid' => $this->money($group->sum('amount_paid')),
            ])
            ->sortKeysDesc()
            ->take(31)
            ->values();

        return $this->reportPayload('Daily Sales', 'Sales totals grouped by day from POS orders.', ['date' => 'Date', 'orders' => 'Orders', 'sales' => 'Sales', 'paid' => 'Paid'], $rows, [
            ['label' => 'Orders', 'value' => number_format($orders->count())],
            ['label' => 'Sales', 'value' => $this->money($orders->sum('total'))],
            ['label' => 'Paid', 'value' => $this->money($orders->sum('amount_paid'))],
        ]);
    }

    private function productSalesReport(): array
    {
        $items = Schema::hasTable('pos_order_items')
            ? PosOrderItem::with('product', 'order')->latest('id')->limit(1000)->get()->filter(fn ($item) => $item->order && $item->order->status !== 'cancelled')
            : collect();

        $rows = $items
            ->groupBy(fn ($item) => $item->product?->name ?: $item->title ?: $item->description ?: 'Item')
            ->map(fn ($group, $name) => [
                'product' => $name,
                'quantity' => number_format((float) $group->sum('quantity'), 3),
                'revenue' => $this->money($group->sum('line_total')),
            ])
            ->sortByDesc(fn ($row) => (float) str_replace(',', '', $row['revenue']))
            ->take(30)
            ->values();

        return $this->reportPayload('Product Sales', 'Best-selling products by quantity and revenue.', ['product' => 'Product', 'quantity' => 'Qty Sold', 'revenue' => 'Revenue'], $rows, [
            ['label' => 'Products', 'value' => number_format($rows->count())],
            ['label' => 'Units', 'value' => number_format((float) $items->sum('quantity'), 3)],
            ['label' => 'Revenue', 'value' => $this->money($items->sum('line_total'))],
        ]);
    }

    private function stockLevelsReport(): array
    {
        $products = Product::orderBy('name')->limit(80)->get();
        $rows = $products->map(fn ($product) => [
            'sku' => $product->sku ?: '-',
            'product' => $product->name,
            'stock' => $product->formattedStock(),
            'reorder' => number_format((float) $product->reorder_level, 3),
            'status' => $product->isLowStock() ? 'Low Stock' : ($product->is_active ? 'Active' : 'Inactive'),
        ]);

        return $this->reportPayload('Stock Levels', 'Current product stock and reorder thresholds.', ['sku' => 'SKU', 'product' => 'Product', 'stock' => 'Stock', 'reorder' => 'Reorder', 'status' => 'Status'], $rows, [
            ['label' => 'Products', 'value' => number_format($products->count())],
            ['label' => 'Low Stock', 'value' => number_format($products->filter->isLowStock()->count())],
            ['label' => 'Stock Value', 'value' => $this->money($products->sum(fn ($product) => (float) $product->stock_quantity * (float) $product->cost_price))],
        ]);
    }

    private function returnsReport(): array
    {
        $returns = Schema::hasTable('retail_return_authorizations')
            ? RetailReturnAuthorization::with('client', 'order')->latest()->limit(80)->get()
            : collect();
        $rows = $returns->map(fn ($return) => [
            'return' => $return->return_number ?? '#'.$return->id,
            'customer' => $return->client?->name ?: $return->order?->customer_name ?: '-',
            'status' => $return->approval_status ?? $return->status ?? 'Pending',
            'refund' => $this->money($return->refund_total),
            'requested' => $return->requested_at?->format('d M Y') ?: optional($return->created_at)->format('d M Y'),
        ]);

        return $this->reportPayload('Returns', 'Retail return authorizations and refund totals.', ['return' => 'Return', 'customer' => 'Customer', 'status' => 'Status', 'refund' => 'Refund', 'requested' => 'Requested'], $rows, [
            ['label' => 'Returns', 'value' => number_format($returns->count())],
            ['label' => 'Refund Total', 'value' => $this->money($returns->sum('refund_total'))],
        ]);
    }

    private function monthlySalesReport(): array
    {
        $orders = PosOrder::where('status', '!=', 'cancelled')->latest('order_date')->limit(1000)->get();
        $rows = $orders
            ->groupBy(fn ($order) => optional($order->order_date)->format('Y-m') ?: 'No date')
            ->map(fn ($group, $month) => [
                'month' => $month,
                'orders' => number_format($group->count()),
                'sales' => $this->money($group->sum('total')),
                'paid' => $this->money($group->sum('amount_paid')),
            ])
            ->sortKeysDesc()
            ->take(24)
            ->values();

        return $this->reportPayload('Monthly Sales', 'Sales grouped by month.', ['month' => 'Month', 'orders' => 'Orders', 'sales' => 'Sales', 'paid' => 'Paid'], $rows);
    }

    private function cashierSalesReport(): array
    {
        $orders = PosOrder::with('retailExtension.cashier')->where('status', '!=', 'cancelled')->latest('order_date')->limit(500)->get();
        $rows = $orders
            ->groupBy(fn ($order) => $order->retailExtension?->cashier?->name ?: 'Unassigned')
            ->map(fn ($group, $cashier) => [
                'cashier' => $cashier,
                'orders' => number_format($group->count()),
                'sales' => $this->money($group->sum('total')),
                'paid' => $this->money($group->sum('amount_paid')),
            ])
            ->sortByDesc(fn ($row) => (float) str_replace(',', '', $row['sales']))
            ->values();

        return $this->reportPayload('Cashier Sales', 'Sales grouped by cashier where POS cashier data exists.', ['cashier' => 'Cashier', 'orders' => 'Orders', 'sales' => 'Sales', 'paid' => 'Paid'], $rows);
    }

    private function replenishmentReport(string $title, string $subtitle): array
    {
        $plans = Schema::hasTable('retail_replenishment_plans')
            ? RetailReplenishmentPlan::with('product', 'supplier', 'purchaseOrder')->latest()->limit(80)->get()
            : collect();
        $rows = $plans->map(fn ($plan) => [
            'product' => $plan->product?->name ?: '-',
            'supplier' => $plan->supplier?->name ?: '-',
            'available' => number_format((float) $plan->available_stock_qty, 3),
            'reorder_point' => number_format((float) $plan->reorder_point_qty, 3),
            'recommended' => number_format((float) $plan->recommended_order_qty, 3),
            'status' => $plan->status,
        ]);

        return $this->reportPayload($title, $subtitle, ['product' => 'Product', 'supplier' => 'Supplier', 'available' => 'Available', 'reorder_point' => 'Reorder Point', 'recommended' => 'Recommended', 'status' => 'Status'], $rows);
    }

    private function cycleCountVarianceReport(): array
    {
        $counts = Schema::hasTable('retail_cycle_counts') ? RetailCycleCount::with('product', 'branch', 'warehouse')->latest()->limit(80)->get() : collect();
        $rows = $counts->map(fn ($count) => [
            'product' => $count->product?->name ?: '-',
            'location' => collect([$count->branch?->name, $count->warehouse?->name])->filter()->join(' / ') ?: '-',
            'system' => number_format((float) $count->system_quantity, 3),
            'counted' => number_format((float) $count->counted_quantity, 3),
            'variance' => number_format((float) $count->variance_quantity, 3),
            'status' => $count->status,
        ]);

        return $this->reportPayload('Cycle Count Variance', 'Cycle count differences between system and counted stock.', ['product' => 'Product', 'location' => 'Location', 'system' => 'System', 'counted' => 'Counted', 'variance' => 'Variance', 'status' => 'Status'], $rows);
    }

    private function valuationReport(): array
    {
        $balances = Schema::hasTable('retail_inventory_balances') ? RetailInventoryBalance::with('product', 'branch', 'warehouse')->latest()->limit(100)->get() : collect();
        $rows = $balances->map(fn ($balance) => [
            'product' => $balance->product?->name ?: '-',
            'location' => collect([$balance->branch?->name, $balance->warehouse?->name])->filter()->join(' / ') ?: '-',
            'available' => number_format((float) $balance->available_stock, 3),
            'unit_cost' => $this->money($balance->unit_cost),
            'value' => $this->money($balance->stock_value),
        ]);

        return $this->reportPayload('Valuation Reports', 'Inventory valuation from retail stock balances.', ['product' => 'Product', 'location' => 'Location', 'available' => 'Available', 'unit_cost' => 'Unit Cost', 'value' => 'Stock Value'], $rows, [
            ['label' => 'Stock Value', 'value' => $this->money($balances->sum('stock_value'))],
        ]);
    }

    private function loyaltyReport(): array
    {
        $accounts = Schema::hasTable('retail_loyalty_accounts') ? RetailLoyaltyAccount::with('client')->latest()->limit(80)->get() : collect();
        $rows = $accounts->map(fn ($account) => [
            'customer' => $account->client?->name ?: '-',
            'number' => $account->loyalty_number,
            'tier' => $account->tier,
            'balance' => number_format((int) $account->points_balance),
            'earned' => number_format((int) $account->points_earned),
        ]);

        return $this->reportPayload('Loyalty Reports', 'Customer loyalty account balances and tiers.', ['customer' => 'Customer', 'number' => 'Number', 'tier' => 'Tier', 'balance' => 'Balance', 'earned' => 'Earned'], $rows);
    }

    private function purchaseHistoryReport(): array
    {
        $orders = PosOrder::with('client')->where('status', '!=', 'cancelled')->latest('order_date')->limit(80)->get();
        $rows = $orders->map(fn ($order) => [
            'order' => $order->order_number,
            'customer' => $order->client?->name ?: $order->customer_name ?: 'Walk-in',
            'date' => $order->order_date?->format('d M Y') ?: '-',
            'total' => $this->money($order->total),
            'paid' => $this->money($order->amount_paid),
            'status' => $order->status,
        ]);

        return $this->reportPayload('Purchase History', 'Recent POS purchases by customer.', ['order' => 'Order', 'customer' => 'Customer', 'date' => 'Date', 'total' => 'Total', 'paid' => 'Paid', 'status' => 'Status'], $rows);
    }

    private function customerSegmentsReport(): array
    {
        $profiles = Schema::hasTable('retail_customer_profiles') ? RetailCustomerProfile::with('client')->latest()->limit(500)->get() : collect();
        $rows = $profiles
            ->groupBy(fn ($profile) => $profile->customer_segment ?: 'Retail Customer')
            ->map(fn ($group, $segment) => [
                'segment' => $segment,
                'customers' => number_format($group->count()),
                'purchases' => number_format($group->sum('total_purchases')),
                'lifetime_value' => $this->money($group->sum('lifetime_value')),
            ])
            ->values();

        return $this->reportPayload('Customer Segments', 'Customer counts, recorded purchases, and lifetime value by segment.', ['segment' => 'Segment', 'customers' => 'Customers', 'purchases' => 'Purchases', 'lifetime_value' => 'Lifetime Value'], $rows);
    }

    private function personalizedOffersReport(): array
    {
        $offers = Schema::hasTable('retail_customer_offers') ? RetailCustomerOffer::with('client', 'promotion')->latest()->limit(80)->get() : collect();
        $rows = $offers->map(fn ($offer) => [
            'offer' => $offer->offer_name,
            'customer' => $offer->client?->name ?: '-',
            'segment' => $offer->segment ?: '-',
            'status' => $offer->status,
            'valid_until' => $offer->valid_until?->format('d M Y') ?: '-',
            'redemptions' => number_format((int) $offer->redemption_count),
        ]);

        return $this->reportPayload('Personalized Offers', 'Active and draft customer offers.', ['offer' => 'Offer', 'customer' => 'Customer', 'segment' => 'Segment', 'status' => 'Status', 'valid_until' => 'Valid Until', 'redemptions' => 'Redemptions'], $rows);
    }

    private function supplierPerformanceReport(): array
    {
        $suppliers = Supplier::with('retailProfile')->latest()->limit(80)->get();
        $rows = $suppliers->map(fn ($supplier) => [
            'supplier' => $supplier->name,
            'lead_time' => number_format((int) ($supplier->retailProfile?->lead_time_days ?? 0)),
            'delivery_accuracy' => number_format((float) ($supplier->retailProfile?->delivery_accuracy ?? 0), 2).'%',
            'rating' => number_format((float) ($supplier->retailProfile?->rating ?? 0), 2),
            'email' => $supplier->email ?: '-',
        ]);

        return $this->reportPayload('Supplier Performance', 'Supplier lead time, delivery accuracy, and ratings.', ['supplier' => 'Supplier', 'lead_time' => 'Lead Days', 'delivery_accuracy' => 'Delivery Accuracy', 'rating' => 'Rating', 'email' => 'Email'], $rows);
    }

    private function supplierContractsReport(): array
    {
        $contracts = Schema::hasTable('retail_supplier_contracts') ? RetailSupplierContract::with('supplier', 'product')->latest()->limit(80)->get() : collect();
        $rows = $contracts->map(fn ($contract) => [
            'contract' => $contract->contract_number,
            'supplier' => $contract->supplier?->name ?: '-',
            'product' => $contract->product?->name ?: '-',
            'lead_time' => number_format((int) $contract->lead_time_days),
            'status' => $contract->status,
        ]);

        return $this->reportPayload('Supplier Contracts', 'Contract records and supplier service levels.', ['contract' => 'Contract', 'supplier' => 'Supplier', 'product' => 'Product', 'lead_time' => 'Lead Days', 'status' => 'Status'], $rows);
    }

    private function purchaseReports(): array
    {
        $orders = PurchaseOrder::with('supplier')->latest('order_date')->limit(80)->get();
        $rows = $orders->map(fn ($order) => [
            'po' => $order->po_number,
            'supplier' => $order->supplier?->name ?: '-',
            'date' => $order->order_date?->format('d M Y') ?: '-',
            'amount' => $this->money($order->amount),
            'status' => $order->status,
        ]);

        return $this->reportPayload('Purchase Reports', 'Recent purchase orders for retail replenishment and procurement.', ['po' => 'PO', 'supplier' => 'Supplier', 'date' => 'Date', 'amount' => 'Amount', 'status' => 'Status'], $rows);
    }

    private function landedCostReport(): array
    {
        $plans = Schema::hasTable('retail_replenishment_plans') ? RetailReplenishmentPlan::with('product', 'supplier')->latest()->limit(80)->get() : collect();
        $rows = $plans->map(fn ($plan) => [
            'product' => $plan->product?->name ?: '-',
            'supplier' => $plan->supplier?->name ?: '-',
            'qty' => number_format((float) $plan->recommended_order_qty, 3),
            'landed_cost' => $this->money($plan->landed_cost_per_unit),
            'estimated_total' => $this->money($plan->estimated_total_cost),
        ]);

        return $this->reportPayload('Landed Cost', 'Estimated landed cost from replenishment planning.', ['product' => 'Product', 'supplier' => 'Supplier', 'qty' => 'Qty', 'landed_cost' => 'Landed Cost', 'estimated_total' => 'Estimated Total'], $rows);
    }

    private function rmaRestockingReport(): array
    {
        $returns = Schema::hasTable('retail_return_authorizations') ? RetailReturnAuthorization::with('client', 'order')->latest()->limit(80)->get() : collect();
        $rows = $returns->map(fn ($return) => [
            'rma' => $return->return_number ?? '#'.$return->id,
            'customer' => $return->client?->name ?: '-',
            'order' => $return->order?->order_number ?: '-',
            'status' => $return->approval_status ?? $return->status ?? 'Pending',
            'refund' => $this->money($return->refund_total),
        ]);

        return $this->reportPayload('RMA Restocking', 'Return merchandise authorization and restocking workflow.', ['rma' => 'RMA', 'customer' => 'Customer', 'order' => 'Order', 'status' => 'Status', 'refund' => 'Refund'], $rows);
    }

    private function fulfillmentReport(string $type, string $title, string $subtitle): array
    {
        $fulfillments = Schema::hasTable('retail_order_fulfillments')
            ? RetailOrderFulfillment::with('order.client', 'branch', 'warehouse')->where('fulfillment_type', $type)->latest()->limit(80)->get()
            : collect();
        $rows = $fulfillments->map(fn ($fulfillment) => [
            'order' => $fulfillment->order?->order_number ?: '-',
            'customer' => $fulfillment->order?->client?->name ?: '-',
            'branch' => $fulfillment->branch?->name ?: '-',
            'status' => $fulfillment->routing_status,
            'carrier' => $fulfillment->carrier ?: '-',
            'tracking' => $fulfillment->tracking_number ?: '-',
        ]);

        return $this->reportPayload($title, $subtitle, ['order' => 'Order', 'customer' => 'Customer', 'branch' => 'Branch', 'status' => 'Status', 'carrier' => 'Carrier', 'tracking' => 'Tracking'], $rows);
    }

    private function branchRevenueReport(): array
    {
        $orders = RetailOrder::with('branch')->where('status', '!=', 'Cancelled')->latest('order_date')->limit(500)->get();
        $rows = $orders
            ->groupBy(fn ($order) => $order->branch?->name ?: 'Unassigned')
            ->map(fn ($group, $branch) => [
                'branch' => $branch,
                'orders' => number_format($group->count()),
                'revenue' => $this->money($group->sum('total')),
                'tax' => $this->money($group->sum('tax_total')),
            ])
            ->sortByDesc(fn ($row) => (float) str_replace(',', '', $row['revenue']))
            ->values();

        return $this->reportPayload('Branch Revenue', 'Retail order revenue grouped by branch.', ['branch' => 'Branch', 'orders' => 'Orders', 'revenue' => 'Revenue', 'tax' => 'Tax'], $rows);
    }

    private function branchProfitabilityReport(): array
    {
        $orders = RetailOrder::with('branch', 'items.product')->where('status', '!=', 'Cancelled')->latest('order_date')->limit(500)->get();
        $rows = $orders
            ->groupBy(fn ($order) => $order->branch?->name ?: 'Unassigned')
            ->map(function ($group, $branch) {
                $revenue = (float) $group->sum('total');
                $cost = (float) $group->flatMap->items->sum(fn ($item) => (float) $item->quantity * (float) ($item->product?->cost_price ?? 0));

                return [
                    'branch' => $branch,
                    'revenue' => $this->money($revenue),
                    'cost' => $this->money($cost),
                    'profit' => $this->money($revenue - $cost),
                ];
            })
            ->values();

        return $this->reportPayload('Branch Profitability', 'Estimated branch gross profit from retail orders and product costs.', ['branch' => 'Branch', 'revenue' => 'Revenue', 'cost' => 'Cost', 'profit' => 'Profit'], $rows);
    }

    private function branchInventoryReport(): array
    {
        $balances = Schema::hasTable('retail_inventory_balances') ? RetailInventoryBalance::with('product', 'branch', 'warehouse')->latest()->limit(100)->get() : collect();
        $rows = $balances->map(fn ($balance) => [
            'branch' => $balance->branch?->name ?: 'Unassigned',
            'warehouse' => $balance->warehouse?->name ?: '-',
            'product' => $balance->product?->name ?: '-',
            'available' => number_format((float) $balance->available_stock, 3),
            'reserved' => number_format((float) $balance->reserved_stock, 3),
            'value' => $this->money($balance->stock_value),
        ]);

        return $this->reportPayload('Branch Inventory', 'Inventory balances by branch and warehouse.', ['branch' => 'Branch', 'warehouse' => 'Warehouse', 'product' => 'Product', 'available' => 'Available', 'reserved' => 'Reserved', 'value' => 'Value'], $rows);
    }

    private function skuProfitabilityReport(RetailEnterpriseOperationsService $enterprise): array
    {
        $profitability = $enterprise->skuProfitability();
        $rows = collect($profitability)->map(fn ($row) => [
            'sku' => $row->sku,
            'revenue' => $this->money($row->revenue),
            'cost' => $this->money($row->cost),
            'profit' => $this->money($row->profit),
        ]);

        return $this->reportPayload('SKU Profitability', 'Revenue, cost, and profit by SKU.', ['sku' => 'SKU', 'revenue' => 'Revenue', 'cost' => 'Cost', 'profit' => 'Profit'], $rows);
    }

    private function vatJurisdictionReport(): array
    {
        $taxes = Schema::hasTable('retail_tax_jurisdictions') ? RetailTaxJurisdiction::latest()->limit(80)->get() : collect();
        $rows = $taxes->map(fn ($tax) => [
            'country' => trim($tax->country.' '.($tax->region ? '/ '.$tax->region : '')),
            'tax' => $tax->tax_name,
            'code' => $tax->tax_code ?: '-',
            'rate' => number_format((float) $tax->tax_rate, 2).'%',
            'currency' => $tax->currency_code,
            'status' => $tax->status,
        ]);

        return $this->reportPayload('VAT/GST Jurisdictions', 'Configured tax jurisdictions and currency mappings.', ['country' => 'Jurisdiction', 'tax' => 'Tax', 'code' => 'Code', 'rate' => 'Rate', 'currency' => 'Currency', 'status' => 'Status'], $rows);
    }

    private function auditReport(): array
    {
        $logs = Schema::hasTable('admin_audit_logs')
            ? AdminAuditLog::query()
                ->when(ActiveBusiness::id(), fn ($query, $businessId) => $query->where('business_id', $businessId))
                ->where('event', 'like', 'retail.%')
                ->latest()
                ->limit(80)
                ->get()
            : collect();
        $rows = $logs->map(fn ($log) => [
            'event' => $log->event,
            'subject' => class_basename((string) $log->subject_type).' #'.$log->subject_id,
            'user' => $log->user_id ?: '-',
            'when' => optional($log->created_at)->format('d M Y H:i') ?: '-',
        ]);

        return $this->reportPayload('Audit Reporting', 'Recent retail audit events captured by the platform.', ['event' => 'Event', 'subject' => 'Subject', 'user' => 'User', 'when' => 'When'], $rows);
    }

    private function reportPayload(string $title, string $subtitle, array $columns, $rows, array $metrics = [], string $empty = 'No rows yet for this report.'): array
    {
        $rows = collect($rows)->values();

        return compact('title', 'subtitle', 'columns', 'rows', 'metrics', 'empty');
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2);
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
