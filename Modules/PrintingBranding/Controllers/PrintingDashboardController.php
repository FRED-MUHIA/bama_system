<?php

namespace Modules\PrintingBranding\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IndustrySetupService;
use Modules\PrintingBranding\Models\Estimate;
use Modules\PrintingBranding\Models\Machine;
use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Services\PrintingDashboardService;
use Modules\PrintingBranding\Services\PrintingFeatureGate;
use Modules\PrintingBranding\Services\PrintingIndustryService;

class PrintingDashboardController extends Controller
{
    public function __invoke(PrintingDashboardService $dashboard, PrintingFeatureGate $gate, PrintingIndustryService $industry)
    {
        $gate->authorize('printing.dashboard');
        $tenant = auth()->user()?->currentTenant;

        return view('printing-branding.dashboard', [
            'tenant' => $tenant,
            'industryDashboard' => app(IndustrySetupService::class)->dashboardFeaturesForTenant($tenant),
            'modules' => $industry->definition()['modules'] ?? [],
            'enabledModules' => $industry->enabledModules(),
            'metrics' => $dashboard->metrics(),
            'charts' => $dashboard->charts(),
            'board' => $dashboard->board(),
            'recentJobs' => ProductionJob::with('client', 'machine')->latest()->limit(8)->get(),
            'recentEstimates' => Estimate::with('client')->latest()->limit(6)->get(),
            'machines' => Machine::latest()->limit(8)->get(),
        ]);
    }
}
