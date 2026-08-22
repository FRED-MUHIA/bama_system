<?php

namespace Modules\Construction\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Services\ConstructionDashboardService;
use Modules\Construction\Services\ConstructionReportingService;

class ConstructionApiController extends Controller
{
    public function dashboard(ConstructionDashboardService $dashboard)
    {
        return response()->json([
            'metrics' => $dashboard->metrics(),
            'charts' => $dashboard->charts(),
            'alerts' => $dashboard->alerts(),
            'profitability' => $dashboard->projectProfitability(),
        ]);
    }

    public function projects()
    {
        return response()->json([
            'data' => ConstructionProjectProfile::with('project', 'client')->latest()->paginate(25),
        ]);
    }

    public function reportExport(string $type, ConstructionReportingService $reports)
    {
        return $reports->csv($type);
    }
}
