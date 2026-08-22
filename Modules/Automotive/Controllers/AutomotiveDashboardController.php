<?php

namespace Modules\Automotive\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IndustrySetupService;
use App\Support\ActiveTenant;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\ServiceBooking;
use Modules\Automotive\Models\Vehicle;
use Modules\Automotive\Services\AutomotiveDashboardService;

class AutomotiveDashboardController extends Controller
{
    public function __invoke(AutomotiveDashboardService $dashboard, IndustrySetupService $industries)
    {
        abort_unless(auth()->user()?->hasPermission('automotive.dashboard'), 403);

        $tenant = ActiveTenant::current();

        return view('automotive.dashboard', [
            'industryDashboard' => $industries->dashboardFeatures('automotive', $tenant?->sub_industry ?: ($tenant?->settings['sub_industry'] ?? 'standard')),
            'metrics' => $dashboard->metrics(),
            'charts' => $dashboard->charts(),
            'alerts' => $dashboard->alerts(),
            'vehicles' => Vehicle::with('client')->latest()->limit(8)->get(),
            'bookings' => ServiceBooking::with('client', 'vehicle')->latest()->limit(8)->get(),
            'jobs' => JobCard::with('client', 'vehicle', 'technician')->latest()->limit(10)->get(),
        ]);
    }
}
