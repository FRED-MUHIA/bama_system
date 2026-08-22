<?php

namespace Modules\Agriculture\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Agriculture\Models\ProduceBatch;
use Modules\Agriculture\Services\AgricultureDashboardService;
use Modules\Agriculture\Services\AgricultureReportingService;
use Modules\Agriculture\Services\AgricultureService;
use Modules\Agriculture\Services\AgricultureTraceabilityService;

class AgricultureApiController extends Controller
{
    public function dashboard(AgricultureDashboardService $dashboard)
    {
        return response()->json(['data' => ['metrics' => $dashboard->metrics(), 'panels' => $dashboard->panels(), 'alerts' => $dashboard->alerts()]]);
    }

    public function store(string $type, Request $request, AgricultureService $service)
    {
        abort_unless($request->user()?->hasPermission($this->permissionFor($type)), 403);

        $method = match ($type) {
            'farm' => 'createFarm',
            'field' => 'createField',
            'crop' => 'createCrop',
            'crop-plan' => 'createCropPlan',
            'activity' => 'createActivity',
            'harvest' => 'createHarvest',
            'herd' => 'createHerd',
            'animal' => 'createAnimal',
            'veterinary' => 'createVeterinaryRecord',
            'breeding' => 'createBreedingEvent',
            'production' => 'createProduction',
            'input' => 'createInput',
            'input-usage' => 'createInputUsage',
            'equipment' => 'createEquipment',
            'equipment-maintenance' => 'createEquipmentMaintenance',
            'sale' => 'createProduceSale',
            'compliance' => 'createCompliance',
            'budget' => 'createBudget',
            default => null,
        };
        abort_unless($method && method_exists($service, $method), 404);

        $record = $service->{$method}($request->all());

        return response()->json(['data' => $record], 201);
    }

    public function reportExport(string $type, AgricultureReportingService $reports)
    {
        $rows = $reports->rows($type);
        abort_if($rows->isEmpty(), 404);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($rows->first()));
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);

        return response(stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=agriculture-{$type}.csv",
        ]);
    }

    public function traceability(ProduceBatch $batch, AgricultureTraceabilityService $traceability)
    {
        return response()->json(['data' => $traceability->chain($batch)]);
    }

    private function permissionFor(string $type): string
    {
        return match ($type) {
            'farm' => 'farms.manage',
            'field' => 'fields.manage',
            'crop' => 'crops.manage',
            'crop-plan' => 'crop_plans.manage',
            'activity' => 'farm_activities.manage',
            'harvest' => 'harvests.manage',
            'herd', 'animal', 'production' => 'livestock.manage',
            'veterinary' => 'veterinary.manage',
            'breeding' => 'breeding.manage',
            'input', 'input-usage' => 'inputs.manage',
            'equipment', 'equipment-maintenance' => 'equipment.manage',
            'sale', 'budget' => 'agriculture.finance',
            'compliance' => 'agriculture.settings',
            default => 'agriculture.manage',
        };
    }
}
