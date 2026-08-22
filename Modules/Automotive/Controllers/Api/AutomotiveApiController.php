<?php

namespace Modules\Automotive\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\Vehicle;
use Modules\Automotive\Services\AutomotiveDashboardService;
use Modules\Automotive\Services\AutomotiveReportingService;

class AutomotiveApiController extends Controller
{
    public function dashboard(AutomotiveDashboardService $dashboard)
    {
        return response()->json([
            'metrics' => $dashboard->metrics(),
            'charts' => $dashboard->charts(),
            'alerts' => $dashboard->alerts(),
        ]);
    }

    public function vehicles()
    {
        return Vehicle::with('client')->latest()->paginate(50);
    }

    public function jobCards()
    {
        return JobCard::with('client', 'vehicle', 'technician')->latest()->paginate(50);
    }

    public function reportExport(string $type, AutomotiveReportingService $reports)
    {
        return $reports->csv($type);
    }
}
