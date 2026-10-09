<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Schema;
use Modules\Retail\Models\RetailExpense;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailDashboardService;

class RetailDashboardController extends Controller
{
    public function __invoke(RetailDashboardService $dashboard, RetailRepository $retail)
    {
        $canViewExpenses = auth()->user()?->hasPermission('expenses.view') ?? false;

        return view('retail.dashboard', [
            'metrics' => $dashboard->overviewMetrics(),
            'lowStockProducts' => $dashboard->lowStockProducts(),
            'recentOrders' => $retail->dashboardSales()->latest()->limit(8)->get(),
            'canViewExpenses' => $canViewExpenses,
            'recentExpenses' => $canViewExpenses && Schema::hasTable('retail_expenses')
                ? RetailExpense::with('branch')->latest('expense_date')->latest('id')->limit(5)->get()
                : collect(),
            'expenseCurrency' => CompanySetting::query()->value('currency_code') ?: 'KES',
        ]);
    }
}
