<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\PosOrder;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailLoyaltyAccount;
use Modules\Retail\Models\RetailWarehouse;
use Shared\Compliance\Etims\Contracts\EtimsComplianceServiceContract;

class RetailDashboardService
{
    public function metrics(): array
    {
        $today = today()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $salesToday = PosOrder::whereDate('order_date', $today)->where('status', '!=', 'cancelled')->sum('amount_paid');
        $salesMonth = PosOrder::whereDate('order_date', '>=', $monthStart)->where('status', '!=', 'cancelled')->sum('amount_paid');
        $transactionsToday = PosOrder::whereDate('order_date', $today)->where('status', '!=', 'cancelled')->count();

        $metrics = [
            'Sales Today' => $salesToday,
            'Sales This Month' => $salesMonth,
            'Gross Revenue' => PosOrder::where('status', '!=', 'cancelled')->sum('total'),
            'Net Revenue' => PosOrder::where('status', '!=', 'cancelled')->sum('amount_paid'),
            'Transactions Today' => $transactionsToday,
            'Average Basket Value' => $transactionsToday ? round($salesToday / $transactionsToday, 2) : 0,
            'Stock Value' => RetailInventoryBalance::sum('stock_value') ?: Product::sum(DB::raw('stock_quantity * cost_price')),
            'Low Stock Alerts' => Product::where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'Active Customers' => Client::count(),
            'Loyalty Members' => RetailLoyaltyAccount::count(),
            'VIP Customers' => \Modules\Retail\Models\RetailCustomerProfile::where('customer_segment', 'VIP Customer')->count(),
            'Warehouses' => RetailWarehouse::count(),
        ];

        if (app()->bound(EtimsComplianceServiceContract::class)) {
            $etims = app(EtimsComplianceServiceContract::class)->metrics('retail');
            $metrics['ETIMS Submitted Invoices'] = $etims['submitted_invoices'];
            $metrics['ETIMS Pending Invoices'] = $etims['pending_invoices'];
            $metrics['ETIMS Failed Submissions'] = $etims['failed_submissions'];
            $metrics['ETIMS Compliance Rate'] = $etims['compliance_rate'];
        }

        return $metrics;
    }

    public function topProducts(int $limit = 5)
    {
        return DB::table('pos_order_items')
            ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_items.pos_order_id')
            ->where('pos_orders.status', '!=', 'cancelled')
            ->select('pos_order_items.title', DB::raw('SUM(pos_order_items.quantity) as quantity'), DB::raw('SUM(pos_order_items.line_total) as total'))
            ->groupBy('pos_order_items.title')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function topCashiers(int $limit = 5)
    {
        return DB::table('retail_sales_extensions')
            ->join('users', 'users.id', '=', 'retail_sales_extensions.cashier_id')
            ->join('pos_orders', 'pos_orders.id', '=', 'retail_sales_extensions.pos_order_id')
            ->select('users.name', DB::raw('COUNT(*) as transactions'), DB::raw('SUM(pos_orders.amount_paid) as revenue'))
            ->groupBy('users.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }
}
