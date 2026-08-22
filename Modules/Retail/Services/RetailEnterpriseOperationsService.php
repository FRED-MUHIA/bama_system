<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\IamService;
use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\RetailCustomerOffer;
use Modules\Retail\Models\RetailCycleCount;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailOrderFulfillment;
use Modules\Retail\Models\RetailReplenishmentPlan;
use Modules\Retail\Models\RetailSupplierContract;
use Modules\Retail\Models\RetailTaxJurisdiction;

class RetailEnterpriseOperationsService
{
    public function __construct(
        private RetailInventoryService $inventory,
        private IamService $iam,
    ) {
    }

    public function createReplenishmentPlan(Product $product, array $context = []): RetailReplenishmentPlan
    {
        $period = (int) ($context['forecast_period_days'] ?? 30);
        $leadTime = (int) ($context['lead_time_days'] ?? $this->supplierLeadTime($context['supplier_id'] ?? $product->retailProfile?->supplier_id));
        $averageDailyDemand = $this->averageDailyDemand($product, max($period, 1));
        $forecastQty = round($averageDailyDemand * $period, 3);
        $safetyStock = round($averageDailyDemand * max($leadTime, 1) * (float) ($context['safety_stock_factor'] ?? 1.5), 3);
        $reorderPoint = round(($averageDailyDemand * max($leadTime, 1)) + $safetyStock, 3);
        $available = $this->availableStock($product, $context);
        $recommendedQty = max(round($reorderPoint - $available, 3), 0);
        $landedCost = $this->landedCostPerUnit($product, $context['landed_cost_components'] ?? []);

        $plan = RetailReplenishmentPlan::create([
            'product_id' => $product->id,
            'branch_id' => $context['branch_id'] ?? null,
            'retail_warehouse_id' => $context['retail_warehouse_id'] ?? null,
            'supplier_id' => $context['supplier_id'] ?? $product->retailProfile?->supplier_id,
            'forecast_period_days' => $period,
            'average_daily_demand' => $averageDailyDemand,
            'demand_forecast_qty' => $forecastQty,
            'lead_time_days' => $leadTime,
            'safety_stock_qty' => $safetyStock,
            'reorder_point_qty' => $reorderPoint,
            'available_stock_qty' => $available,
            'recommended_order_qty' => $recommendedQty,
            'landed_cost_per_unit' => $landedCost,
            'estimated_total_cost' => round($recommendedQty * $landedCost, 2),
            'status' => $recommendedQty > 0 ? 'Proposed' : 'Healthy',
            'notes' => $context['notes'] ?? null,
        ]);

        $this->iam->audit('retail.inventory.replenishment.planned', $plan);

        return $plan->load('product', 'supplier', 'branch', 'warehouse');
    }

    public function generatePurchaseOrder(RetailReplenishmentPlan $plan): ?PurchaseOrder
    {
        if (! $plan->supplier_id || (float) $plan->recommended_order_qty <= 0) {
            return null;
        }

        $po = PurchaseOrder::create([
            'supplier_id' => $plan->supplier_id,
            'po_number' => 'RPO-'.now()->format('Ymd').'-'.$plan->id,
            'order_date' => now()->toDateString(),
            'amount' => $plan->estimated_total_cost,
            'status' => 'Draft',
            'notes' => 'Automated retail replenishment for '.$plan->product?->sku.' qty '.$plan->recommended_order_qty,
        ]);

        $plan->update(['purchase_order_id' => $po->id, 'status' => 'PO Drafted']);
        $this->iam->audit('retail.procurement.purchase-order.generated', $po);

        return $po;
    }

    public function recordCycleCount(Product $product, array $data): RetailCycleCount
    {
        return DB::transaction(function () use ($product, $data) {
            $context = [
                'branch_id' => $data['branch_id'] ?? null,
                'retail_warehouse_id' => $data['retail_warehouse_id'] ?? null,
                'retail_warehouse_bin_id' => $data['retail_warehouse_bin_id'] ?? null,
            ];
            $systemQty = $this->availableStock($product, $context);
            $countedQty = (float) ($data['counted_quantity'] ?? $systemQty);
            $variance = round($countedQty - $systemQty, 3);

            $count = RetailCycleCount::create($context + [
                'product_id' => $product->id,
                'counted_by' => $data['counted_by'] ?? auth()->id(),
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'counted_at' => $data['counted_at'] ?? now(),
                'system_quantity' => $systemQty,
                'counted_quantity' => $countedQty,
                'variance_quantity' => $variance,
                'status' => abs($variance) > 0 ? 'Variance Posted' : 'Matched',
                'notes' => $data['notes'] ?? null,
            ]);

            if ($variance != 0.0) {
                $this->inventory->adjust($product, $variance, 'available_stock', $context + [
                    'reference' => 'Cycle count '.$count->id,
                    'notes' => 'Cycle count variance adjustment.',
                ], $count);
            }

            $this->iam->audit('retail.warehouse.cycle-count.recorded', $count);

            return $count->load('product', 'branch', 'warehouse', 'bin');
        });
    }

    public function routeFulfillment(RetailOrder $order, array $data): RetailOrderFulfillment
    {
        $branchId = $data['branch_id'] ?? $order->branch_id ?? $this->branchWithAvailability($order);

        $fulfillment = $order->fulfillment()->updateOrCreate(
            ['retail_order_id' => $order->id],
            [
                'branch_id' => $branchId,
                'retail_warehouse_id' => $data['retail_warehouse_id'] ?? null,
                'fulfillment_type' => $data['fulfillment_type'],
                'routing_status' => $data['routing_status'] ?? 'Routed',
                'routed_at' => now(),
                'ready_for_pickup_at' => $data['fulfillment_type'] === 'BOPIS' ? now() : null,
                'carrier' => $data['carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        $order->update(['status' => $data['order_status'] ?? ($data['fulfillment_type'] === 'BOPIS' ? 'Packed' : 'Confirmed')]);
        $this->iam->audit('retail.omnichannel.fulfillment.routed', $fulfillment);

        return $fulfillment->load('order.items.product', 'branch', 'warehouse');
    }

    public function createCustomerOffer(Client $client, array $data = []): RetailCustomerOffer
    {
        $history = $this->purchaseHistory($client);
        $topProducts = $history['top_products']->pluck('product_id')->filter()->values()->all();
        $segment = $client->retailProfile?->customer_segment ?? $data['segment'] ?? 'Retail Customer';

        $offer = RetailCustomerOffer::create([
            'client_id' => $client->id,
            'retail_promotion_id' => $data['retail_promotion_id'] ?? null,
            'offer_name' => $data['offer_name'] ?? $segment.' personalized basket offer',
            'offer_type' => $data['offer_type'] ?? 'Personalized Offer',
            'segment' => $segment,
            'behavior_summary' => [
                'total_spend' => $history['total_spend'],
                'transactions' => $history['transactions'],
                'channels' => $history['channels'],
            ],
            'recommended_products' => $data['recommended_products'] ?? $topProducts,
            'valid_from' => $data['valid_from'] ?? now()->toDateString(),
            'valid_until' => $data['valid_until'] ?? now()->addMonth()->toDateString(),
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->iam->audit('retail.marketing.offer.created', $offer);

        return $offer->load('client', 'promotion');
    }

    public function storeSupplierContract(Supplier $supplier, array $data): RetailSupplierContract
    {
        $contract = RetailSupplierContract::updateOrCreate(
            ['supplier_id' => $supplier->id, 'contract_number' => $data['contract_number']],
            $data + [
                'lead_time_days' => $data['lead_time_days'] ?? $supplier->retailProfile?->lead_time_days ?? 0,
                'scorecard' => $data['scorecard'] ?? [
                    'rating' => (float) ($supplier->retailProfile?->rating ?? 0),
                    'delivery_accuracy' => (float) ($supplier->retailProfile?->delivery_accuracy ?? 0),
                    'lead_time_days' => (int) ($supplier->retailProfile?->lead_time_days ?? 0),
                ],
                'status' => $data['status'] ?? 'Active',
            ]
        );

        $this->iam->audit('retail.supplier.contract.saved', $contract);

        return $contract->load('supplier', 'product');
    }

    public function storeTaxJurisdiction(array $data): RetailTaxJurisdiction
    {
        $jurisdiction = RetailTaxJurisdiction::updateOrCreate(
            [
                'country' => $data['country'],
                'region' => $data['region'] ?? null,
                'tax_code' => $data['tax_code'] ?? null,
            ],
            $data + ['status' => $data['status'] ?? 'Active']
        );

        $this->iam->audit('retail.compliance.tax-jurisdiction.saved', $jurisdiction);

        return $jurisdiction;
    }

    public function purchaseHistory(Client $client): array
    {
        $posOrders = PosOrder::with('items.product', 'retailExtension')
            ->where('client_id', $client->id)
            ->where('status', '!=', 'cancelled')
            ->get();
        $retailOrders = RetailOrder::with('items.product')
            ->where('client_id', $client->id)
            ->where('status', '!=', 'Cancelled')
            ->get();

        $topProducts = $posOrders->flatMap->items
            ->merge($retailOrders->flatMap->items)
            ->groupBy('product_id')
            ->map(fn ($items, $productId) => (object) [
                'product_id' => $productId,
                'name' => $items->first()->product?->name ?: $items->first()->title,
                'quantity' => $items->sum('quantity'),
                'total' => $items->sum('line_total'),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'transactions' => $posOrders->count() + $retailOrders->count(),
            'total_spend' => round($posOrders->sum('total') + $retailOrders->sum('total'), 2),
            'channels' => $posOrders->pluck('retailExtension.channel')->filter()->merge($retailOrders->pluck('channel'))->unique()->values()->all(),
            'top_products' => $topProducts,
        ];
    }

    public function skuProfitability()
    {
        return DB::table('pos_order_items')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_items.pos_order_id')
            ->leftJoin('products', 'products.id', '=', 'pos_order_items.product_id')
            ->where('pos_orders.status', '!=', 'cancelled')
            ->select(
                'pos_order_items.product_id',
                DB::raw('COALESCE(products.sku, pos_order_items.title) as sku'),
                DB::raw('SUM(pos_order_items.line_total) as revenue'),
                DB::raw('SUM(pos_order_items.quantity * COALESCE(products.cost_price, 0)) as cost'),
                DB::raw('SUM(pos_order_items.line_total - (pos_order_items.quantity * COALESCE(products.cost_price, 0))) as profit')
            )
            ->groupBy('pos_order_items.product_id', 'products.sku', 'pos_order_items.title')
            ->orderByDesc('profit')
            ->limit(20)
            ->get();
    }

    private function averageDailyDemand(Product $product, int $days): float
    {
        $quantity = DB::table('pos_order_items')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_items.pos_order_id')
            ->where('pos_order_items.product_id', $product->id)
            ->where('pos_orders.status', '!=', 'cancelled')
            ->whereDate('pos_orders.order_date', '>=', now()->subDays($days)->toDateString())
            ->sum('pos_order_items.quantity');

        return round(((float) $quantity) / max($days, 1), 3);
    }

    private function availableStock(Product $product, array $context): float
    {
        $query = RetailInventoryBalance::where('product_id', $product->id);

        foreach (['branch_id', 'retail_warehouse_id', 'retail_warehouse_bin_id'] as $field) {
            if (array_key_exists($field, $context)) {
                $query->where($field, $context[$field]);
            }
        }

        $retailStock = (float) $query->sum('available_stock');

        return $retailStock > 0 ? $retailStock : (float) $product->stock_quantity;
    }

    private function landedCostPerUnit(Product $product, array $components): float
    {
        return round((float) ($product->cost_price ?? 0) + collect($components)->sum(fn ($value) => (float) $value), 2);
    }

    private function supplierLeadTime(?int $supplierId): int
    {
        return $supplierId ? (int) (Supplier::find($supplierId)?->retailProfile?->lead_time_days ?? 0) : 0;
    }

    private function branchWithAvailability(RetailOrder $order): ?int
    {
        foreach ($order->items as $item) {
            $branchId = RetailInventoryBalance::where('product_id', $item->product_id)
                ->where('available_stock', '>=', $item->quantity)
                ->value('branch_id');
            if ($branchId) {
                return (int) $branchId;
            }
        }

        return null;
    }
}
