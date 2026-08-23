<?php

namespace Modules\PrintingBranding\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Services\PrintingDashboardService;
use Modules\PrintingBranding\Services\PrintingFeatureGate;
use Modules\PrintingBranding\Services\PrintingReportingService;
use Modules\PrintingBranding\Services\ProductionJobService;
use Modules\PrintingBranding\Services\WasteService;

class PrintingApiController extends Controller
{
    public function dashboard(PrintingDashboardService $dashboard, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing.dashboard');

        return response()->json(['data' => ['metrics' => $dashboard->metrics(), 'charts' => $dashboard->charts()]]);
    }

    public function mobileJob(ProductionJob $job, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');

        return response()->json([
            'data' => $job->load('client', 'ticket', 'artworks', 'reservations.material'),
            'actions' => ['Start Job', 'Pause', 'Complete', 'Report Problem', 'Record Waste', 'Upload Photo', 'Update Status'],
        ]);
    }

    public function updateStatus(Request $request, ProductionJob $job, ProductionJobService $service, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');
        $data = $request->validate(['status' => ['required', 'string', 'max:80']]);

        return response()->json(['data' => $service->updateStatus($job, $data['status'])]);
    }

    public function recordWaste(Request $request, WasteService $waste, PrintingFeatureGate $gate)
    {
        $gate->authorize('production.execute');

        return response()->json(['data' => $waste->record($request->validate([
            'job_id' => ['nullable', 'exists:printing_jobs,id'],
            'material_id' => ['nullable', 'exists:printing_materials,id'],
            'employee_id' => ['nullable', $this->activeBusinessUserExistsRule()],
            'machine_id' => ['nullable', 'exists:printing_machines,id'],
            'waste_type' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]))], 201);
    }

    public function reportExport(Request $request, string $type, PrintingReportingService $reports, PrintingFeatureGate $gate)
    {
        $gate->authorize('printing_reports.view');
        $date = $request->validate(['date' => ['nullable', 'date']])['date'] ?? null;

        return $reports->export($type, $date);
    }
}
