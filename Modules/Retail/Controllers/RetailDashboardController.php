<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailDashboardService;

class RetailDashboardController extends Controller
{
    public function __invoke(RetailDashboardService $dashboard, RetailRepository $retail)
    {
        return view('retail.dashboard', [
            'metrics' => $dashboard->overviewMetrics(),
            'lowStockProducts' => $dashboard->lowStockProducts(),
            'recentOrders' => $retail->dashboardSales()->latest()->limit(8)->get(),
        ]);
    }
}
