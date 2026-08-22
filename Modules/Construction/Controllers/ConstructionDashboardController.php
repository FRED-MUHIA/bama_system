<?php

namespace Modules\Construction\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IndustrySetupService;
use App\Support\ActiveTenant;
use Modules\Construction\Models\ConstructionBoq;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Models\ConstructionSiteReport;
use Modules\Construction\Services\ConstructionDashboardService;
use Modules\Construction\Services\ConstructionIndustryService;

class ConstructionDashboardController extends Controller
{
    public function __invoke(ConstructionDashboardService $dashboard, ConstructionIndustryService $industry)
    {
        $tenant = ActiveTenant::current();

        return view('construction.dashboard', [
            'industryDashboard' => app(IndustrySetupService::class)->dashboardFeaturesForTenant($tenant),
            'enabledModules' => $industry->enabledModules(),
            'metrics' => $dashboard->metrics(),
            'charts' => $dashboard->charts(),
            'alerts' => $dashboard->alerts(),
            'profitability' => $dashboard->projectProfitability(),
            'projects' => ConstructionProjectProfile::with('project', 'client')->latest()->limit(8)->get(),
            'boqs' => ConstructionBoq::with('project')->latest()->limit(5)->get(),
            'reports' => ConstructionSiteReport::with('project', 'site')->latest()->limit(5)->get(),
        ]);
    }
}
