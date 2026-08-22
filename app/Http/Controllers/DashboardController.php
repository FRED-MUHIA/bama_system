<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\SupplierInvoice;
use App\Services\DashboardWidgetRegistry;
use App\Services\IndustrySetupService;
use App\Services\ModuleRegistry;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use App\Support\DatabasePlatform;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()?->role === 'super_admin') {
            return redirect()->route('platform.dashboard');
        }

        if ($dashboardRoute = $this->industryDashboardRoute()) {
            return redirect()->route($dashboardRoute);
        }

        $settings = CompanySetting::first();
        $performance = $this->performanceData($request, $settings);

        $cards = [
            'Invoices' => Invoice::source()->count(),
            'Quotations' => Quotation::count(),
            'Receipts' => Receipt::count(),
            'POS Orders' => PosOrder::count(),
            'Paid' => Invoice::source()->where('payment_status', 'paid')->count(),
            'Unpaid' => Invoice::source()->where('payment_status', 'unpaid')->count(),
            'Pending Payments' => Invoice::source()->whereIn('payment_status', ['unpaid', 'partial'])->sum('balance')
                + PosOrder::where('status', '!=', 'cancelled')->selectRaw('SUM(GREATEST(total - amount_paid, 0)) as balance')->value('balance'),
            'POS Revenue' => PosOrder::where('status', '!=', 'cancelled')->sum('amount_paid'),
            'Products' => Product::count(),
        ];

        if (Client::supportsCompanyStructure()) {
            $cards['Projects'] = Project::count();
        }

        if (Schema::hasTable('project_costs')) {
            $projects = Project::with('invoices', 'receiptAllocations', 'costs', 'expenses', 'supplierInvoices')->get();
            $revenue = $projects->sum(fn ($project) => $project->revenue());
            $collected = $projects->sum(fn ($project) => $project->collected());
            $costs = $projects->sum(fn ($project) => $project->actualCost());
            $cards['Receivables'] = max($revenue - $collected, 0);
            $cards['Collected'] = $collected;
            $cards['Profit'] = $revenue - $costs;
            $cards['Tax Due'] = Invoice::source()->sum('tax_total');
            $cards['Supplier Due'] = SupplierInvoice::query()->selectRaw('SUM(GREATEST(total - amount_paid, 0)) as total')->value('total') ?? 0;
        }

        return view('dashboard', [
            'cards' => $cards,
            'widgets' => app(DashboardWidgetRegistry::class)->forUser($request->user()?->id),
            'industryDashboard' => app(IndustrySetupService::class)->dashboardFeaturesForTenant($request->user()?->currentTenant),
            'industryHero' => $this->industryHero(),
            'clients' => Client::latest()->limit(6)->get(),
            'recentInvoices' => Invoice::source()->with('client')->latest()->limit(5)->get(),
            'recentQuotations' => Quotation::with('client')->latest()->limit(5)->get(),
            'recentReceipts' => Receipt::with('invoice.client')->latest()->limit(5)->get(),
            'recentOrders' => PosOrder::with('client')->latest()->limit(5)->get(),
            'posStats' => [
                'todaySales' => PosOrder::whereDate('order_date', today())->where('status', '!=', 'cancelled')->sum('amount_paid'),
                'monthSales' => PosOrder::whereBetween('order_date', [now()->startOfMonth(), now()->endOfMonth()])->where('status', '!=', 'cancelled')->sum('amount_paid'),
                'pendingOrders' => PosOrder::where('status', 'pending')->count(),
                'averageOrder' => PosOrder::where('status', '!=', 'cancelled')->avg('total') ?? 0,
            ],
            'topProducts' => PosOrderItem::query()
                ->selectRaw('COALESCE(products.name, pos_order_items.title, pos_order_items.description) as name, SUM(pos_order_items.quantity) as qty, SUM(pos_order_items.line_total) as total')
                ->join('pos_orders', 'pos_orders.id', '=', 'pos_order_items.pos_order_id')
                ->leftJoin('products', 'products.id', '=', 'pos_order_items.product_id')
                ->where('pos_orders.business_id', ActiveBusiness::id())
                ->where('pos_orders.status', '!=', 'cancelled')
                ->groupByRaw('COALESCE(products.name, pos_order_items.title, pos_order_items.description)')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'performance' => $performance,
            'settings' => $settings,
        ]);
    }

    private function industryDashboardRoute(): ?string
    {
        $industry = Str::of(ActiveTenant::current()?->industry ?: ActiveBusiness::current()?->industry ?: '')
            ->snake(' ')
            ->slug('-')
            ->toString();

        $route = match ($industry) {
            'construction' => ['construction.dashboard', 'construction_project_profiles', 'construction'],
            'automotive' => ['automotive.dashboard', 'automotive_job_cards', 'automotive'],
            'printing-branding' => ['printing-branding.dashboard', 'printing_estimates', 'printing-branding'],
            default => null,
        };

        if (! $route) {
            return null;
        }

        [$name, $table, $module] = $route;

        return Route::has($name)
            && Schema::hasTable($table)
            && app(ModuleRegistry::class)->enabledSlug($module)
                ? $name
                : null;
    }

    private function performanceData(Request $request, ?CompanySetting $settings): array
    {
        [$period, $start, $end, $bucket] = $this->dateWindow($request);
        $orders = PosOrder::query()->whereBetween('order_date', [$start->toDateString(), $end->toDateString()]);
        $activeOrders = (clone $orders)->where('status', '!=', 'cancelled');
        $orderValue = (float) (clone $activeOrders)->sum('total');
        $collected = (float) (clone $activeOrders)->sum('amount_paid');
        $outstanding = max($orderValue - $collected, 0);
        $orderCount = (clone $orders)->count();
        $transactionCount = (clone $activeOrders)->where('amount_paid', '>', 0)->count();
        $pendingCount = (clone $orders)->where('status', 'pending')->count();
        $paidCount = (clone $orders)->where('status', 'paid')->count();
        $approvedCount = (clone $orders)->where('status', 'approved')->count();
        $cancelledCount = (clone $orders)->where('status', 'cancelled')->count();
        $averageOrder = $orderCount > 0 ? $orderValue / $orderCount : 0;

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'bucket' => $bucket,
            'currencyCode' => $settings->currency_code ?? 'KES',
            'locale' => $settings->locale ?? 'en_KE',
            'revenue' => $collected,
            'revenueFormatted' => $this->money($collected, $settings),
            'orderValue' => $orderValue,
            'orderValueFormatted' => $this->money($orderValue, $settings),
            'outstanding' => $outstanding,
            'outstandingFormatted' => $this->money($outstanding, $settings),
            'orders' => $orderCount,
            'transactions' => $transactionCount,
            'pending' => $pendingCount,
            'paid' => $paidCount,
            'approved' => $approvedCount,
            'cancelled' => $cancelledCount,
            'averageOrder' => $averageOrder,
            'averageOrderFormatted' => $this->money($averageOrder, $settings),
            'chart' => $this->chartData($start, $end, $bucket, $settings),
        ];
    }

    private function industryHero(): array
    {
        $industry = Str::of(ActiveBusiness::current()?->industry ?: ActiveBusiness::current()?->tenant?->industry ?: 'professional-services')
            ->snake(' ')
            ->slug('-')
            ->toString();
        $industry = match ($industry) {
            'fitness-gym', 'gym', 'health-fitness' => 'fitness',
            'hotel', 'hotels', 'restaurant', 'restaurants' => 'hospitality',
            'commerce', 'pos', 'point-of-sale' => 'retail',
            'salon-spa', 'salon-and-spa', 'spa' => 'salon',
            default => $industry,
        };

        $heroes = [
            'fitness' => [
                'kicker' => 'FITNESS OPERATIONS',
                'title' => ['Member growth.', 'Healthy routines.'],
                'copy' => 'Monitor memberships, check-ins, trainers, classes and wellness progress from one gym operations workspace.',
                'status' => 'GYM STATUS · LIVE',
                'nodes' => ['MEMBERS', 'CHECK-IN', 'TRAINERS', 'CLASSES', 'PROGRAMS', 'PAYMENTS'],
                'actions' => [
                    ['label' => 'Check-In', 'route' => 'fitness.check-in.index', 'icon' => 'bi-box-arrow-in-right', 'primary' => true],
                    ['label' => 'Members', 'route' => 'fitness.members.index', 'icon' => 'bi-people'],
                    ['label' => 'Memberships', 'route' => 'fitness.memberships.index', 'icon' => 'bi-card-checklist'],
                    ['label' => 'Classes', 'route' => 'fitness.classes.index', 'icon' => 'bi-calendar3'],
                    ['label' => 'Nutrition', 'route' => 'fitness.nutrition.index', 'icon' => 'bi-egg-fried'],
                ],
            ],
            'hospitality' => [
                'kicker' => 'HOSPITALITY CONTROL',
                'title' => ['Guest flow.', 'Service clarity.'],
                'copy' => 'Track reservations, rooms, guests, front desk activity and hospitality revenue in one connected workspace.',
                'status' => 'PROPERTY STATUS · CONNECTED',
                'nodes' => ['ROOMS', 'GUESTS', 'FRONT DESK', 'RESERVATIONS', 'HOUSEKEEPING', 'BILLING'],
                'actions' => [
                    ['label' => 'Reservations', 'route' => 'hospitality.reservations.index', 'icon' => 'bi-calendar-check', 'primary' => true],
                    ['label' => 'Rooms', 'route' => 'hospitality.rooms.index', 'icon' => 'bi-door-open'],
                    ['label' => 'Front Desk', 'route' => 'hospitality.front-desk.index', 'icon' => 'bi-building-check'],
                    ['label' => 'Guests', 'route' => 'hospitality.guests.index', 'icon' => 'bi-person-lines-fill'],
                    ['label' => 'Reports', 'route' => 'hospitality.reports.index', 'icon' => 'bi-bar-chart'],
                ],
            ],
            'retail' => [
                'kicker' => 'RETAIL INTELLIGENCE',
                'title' => ['Sales moving.', 'Stock visible.'],
                'copy' => 'Monitor POS orders, product movement, customers, invoices and daily retail revenue from one counter-ready dashboard.',
                'status' => 'STORE STATUS · ACTIVE',
                'nodes' => ['SALES', 'STOCK', 'CLIENTS', 'PRODUCTS', 'PAYMENTS', 'REPORTS'],
                'actions' => [
                    ['label' => 'POS Order', 'route' => 'pos-orders.create', 'icon' => 'bi-shop', 'primary' => true],
                    ['label' => 'Products', 'route' => 'products.index', 'icon' => 'bi-box-seam'],
                    ['label' => 'Client', 'route' => 'clients.create', 'icon' => 'bi-person-plus'],
                    ['label' => 'Invoice', 'route' => 'invoices.create', 'icon' => 'bi-plus-circle'],
                    ['label' => 'Reports', 'route' => 'pos-orders.report', 'icon' => 'bi-graph-up'],
                ],
            ],
            'salon' => [
                'kicker' => 'SALON & SPA OPERATIONS',
                'title' => ['Appointments flowing.', 'Clients returning.'],
                'copy' => 'Coordinate bookings, staff schedules, services, loyalty, product usage and wellness programs from one connected salon workspace.',
                'status' => 'SALON STATUS · LIVE',
                'nodes' => ['CLIENTS', 'STAFF', 'SERVICES', 'POS', 'LOYALTY', 'REPORTS'],
                'actions' => [
                    ['label' => 'Appointment', 'route' => 'salon.appointments.index', 'icon' => 'bi-calendar-plus', 'primary' => true],
                    ['label' => 'Clients', 'route' => 'salon.clients.index', 'icon' => 'bi-person-hearts'],
                    ['label' => 'Services', 'route' => 'salon.services.index', 'icon' => 'bi-scissors'],
                    ['label' => 'Staff', 'route' => 'salon.staff.index', 'icon' => 'bi-person-workspace'],
                    ['label' => 'Reports', 'route' => 'salon.reports.index', 'icon' => 'bi-bar-chart'],
                ],
            ],
            'agriculture' => [
                'kicker' => 'AGRICULTURE OPERATIONS',
                'title' => ['Fields, stock, livestock.', 'One farm command.'],
                'copy' => 'Track farms, crop plans, input stock, harvest batches, livestock health, produce sales and compliance from one agriculture workspace.',
                'status' => 'FARM STATUS · ACTIVE',
                'nodes' => ['FARMS', 'FIELDS', 'CROPS', 'INPUTS', 'HARVEST', 'SALES'],
                'actions' => [
                    ['label' => 'Farms', 'route' => 'agriculture.dashboard', 'params' => ['section' => 'farms'], 'icon' => 'bi-flower1', 'primary' => true],
                    ['label' => 'Crops', 'route' => 'agriculture.dashboard', 'params' => ['section' => 'crops'], 'icon' => 'bi-calendar2-week'],
                    ['label' => 'Harvest', 'route' => 'agriculture.dashboard', 'params' => ['section' => 'harvest'], 'icon' => 'bi-basket'],
                    ['label' => 'Livestock', 'route' => 'agriculture.dashboard', 'params' => ['section' => 'livestock'], 'icon' => 'bi-heart-pulse'],
                    ['label' => 'Reports', 'route' => 'agriculture.reports.index', 'icon' => 'bi-bar-chart'],
                ],
            ],
            'construction' => [
                'kicker' => 'PROJECT OPERATIONS',
                'title' => ['Work in motion.', 'Costs under control.'],
                'copy' => 'Coordinate projects, procurement, supplier documents, invoices and profitability across active job sites.',
                'status' => 'PROJECT STATUS · CONNECTED',
                'nodes' => ['PROJECTS', 'PROCUREMENT', 'SUPPLIERS', 'CLIENTS', 'FINANCE', 'REPORTS'],
                'actions' => [
                    ['label' => 'Project', 'route' => 'projects.create', 'icon' => 'bi-kanban', 'primary' => true],
                    ['label' => 'Procurement', 'route' => 'erp.procurement', 'icon' => 'bi-truck'],
                    ['label' => 'Quotation', 'route' => 'quotations.create', 'icon' => 'bi-file-earmark-plus'],
                    ['label' => 'Invoice', 'route' => 'invoices.create', 'icon' => 'bi-plus-circle'],
                    ['label' => 'Client', 'route' => 'clients.create', 'icon' => 'bi-person-plus'],
                ],
            ],
            'professional-services' => [
                'kicker' => 'OPERATIONAL INTELLIGENCE',
                'title' => ['Connected systems.', 'Clear decisions.'],
                'copy' => 'Monitor commercial activity, projects, finance and client operations through one dependable workspace built for scale.',
                'status' => 'SYSTEM STATUS · CONNECTED',
                'nodes' => ['FINANCE', 'CLIENTS', 'PROJECTS', 'REPORTS', 'COMMERCE', 'DATA'],
                'actions' => [
                    ['label' => 'Invoice', 'route' => 'invoices.create', 'icon' => 'bi-plus-circle', 'primary' => true],
                    ['label' => 'POS Order', 'route' => 'pos-orders.create', 'icon' => 'bi-shop'],
                    ['label' => 'Quotation', 'route' => 'quotations.create', 'icon' => 'bi-file-earmark-plus'],
                    ['label' => 'Project', 'route' => 'projects.create', 'icon' => 'bi-kanban', 'requires_projects' => true],
                    ['label' => 'Client', 'route' => 'clients.create', 'icon' => 'bi-person-plus'],
                ],
            ],
        ];

        $hero = $heroes[$industry] ?? $heroes['professional-services'];
        $hero['actions'] = collect($hero['actions'])
            ->filter(fn ($action) => empty($action['requires_projects']) || Client::supportsCompanyStructure())
            ->filter(fn ($action) => Route::has($action['route']))
            ->values()
            ->all();

        return $hero;
    }

    private function dateWindow(Request $request): array
    {
        $period = $request->query('period', 'monthly');
        $now = now();

        return match ($period) {
            'daily' => [$period, $now->copy()->startOfDay(), $now->copy()->endOfDay(), 'hour'],
            'weekly' => [$period, $now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'day'],
            'yearly' => [$period, $now->copy()->startOfYear(), $now->copy()->endOfYear(), 'month'],
            'custom' => [
                $period,
                Carbon::parse($request->query('from', $now->copy()->startOfMonth()->toDateString()))->startOfDay(),
                Carbon::parse($request->query('to', $now->toDateString()))->endOfDay(),
                'day',
            ],
            default => ['monthly', $now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'day'],
        };
    }

    private function chartData(Carbon $start, Carbon $end, string $bucket, ?CompanySetting $settings): array
    {
        if ($bucket === 'hour') {
            $bucketExpression = DatabasePlatform::dateBucketExpression('order_date', 'hour');

            $raw = PosOrder::query()
                ->selectRaw("{$bucketExpression} as bucket_key, SUM(amount_paid) as total")
                ->whereBetween('order_date', [$start->toDateString(), $end->toDateString()])
                ->where('status', '!=', 'cancelled')
                ->groupByRaw($bucketExpression)
                ->pluck('total', 'bucket_key');

            $labels = $values = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $labels[] = sprintf('%02d:00', $hour);
                $values[] = round((float) ($raw[$hour] ?? 0), 2);
            }

            return compact('labels', 'values');
        }

        if ($bucket === 'month') {
            $bucketExpression = DatabasePlatform::dateBucketExpression('order_date', 'month');

            $raw = PosOrder::query()
                ->selectRaw("{$bucketExpression} as bucket_key, SUM(amount_paid) as total")
                ->whereBetween('order_date', [$start->toDateString(), $end->toDateString()])
                ->where('status', '!=', 'cancelled')
                ->groupByRaw($bucketExpression)
                ->pluck('total', 'bucket_key');

            $labels = $values = [];
            foreach (range(1, 12) as $month) {
                $labels[] = Carbon::create(now()->year, $month, 1)->format('M');
                $values[] = round((float) ($raw[$month] ?? 0), 2);
            }

            return compact('labels', 'values');
        }

        $bucketExpression = DatabasePlatform::dateBucketExpression('order_date', 'day');

        $raw = PosOrder::query()
            ->selectRaw("{$bucketExpression} as bucket_key, SUM(amount_paid) as total")
            ->whereBetween('order_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->groupByRaw($bucketExpression)
            ->pluck('total', 'bucket_key');

        $labels = $values = [];
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('d M');
            $values[] = round((float) ($raw[$key] ?? 0), 2);
        }

        return compact('labels', 'values');
    }

    private function money(float $amount, ?CompanySetting $settings): string
    {
        $currency = $settings->currency_code ?? 'KES';
        $locale = str_replace('-', '_', $settings->locale ?? 'en_KE');

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

            return $formatter->formatCurrency($amount, $currency);
        }

        return $currency.' '.number_format($amount, 2);
    }
}
