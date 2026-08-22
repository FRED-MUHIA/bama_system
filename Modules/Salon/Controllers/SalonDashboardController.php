<?php

namespace Modules\Salon\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IndustrySetupService;
use Modules\Salon\Repositories\SalonRepository;
use Modules\Salon\Services\SalonDashboardService;
use Modules\Salon\Services\SalonFeatureGate;

class SalonDashboardController extends Controller
{
    public function __invoke(SalonDashboardService $dashboard, SalonRepository $repository, SalonFeatureGate $gate)
    {
        $gate->authorize();
        $tenant = auth()->user()?->currentTenant;

        return view('salon.dashboard', [
            'tenant' => $tenant,
            'industryDashboard' => app(IndustrySetupService::class)->dashboardFeaturesForTenant($tenant),
            'metrics' => $dashboard->metrics(),
            'kpis' => $dashboard->kpis(),
            'reports' => $dashboard->reports(),
            'appointments' => $repository->upcomingAppointments()->get(),
            'services' => $repository->activeServices()->limit(8)->get(),
            'staff' => $repository->staff()->limit(8)->get(),
        ]);
    }
}
