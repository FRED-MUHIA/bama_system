<?php

namespace Modules\Construction\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use Modules\Construction\Models\ConstructionBoq;
use Modules\Construction\Models\ConstructionCertificate;
use Modules\Construction\Models\ConstructionDefect;
use Modules\Construction\Models\ConstructionEquipment;
use Modules\Construction\Models\ConstructionMaterial;
use Modules\Construction\Models\ConstructionMaterialConsumption;
use Modules\Construction\Models\ConstructionMaterialRequest;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Models\ConstructionSafetyIncident;
use Modules\Construction\Models\ConstructionSubcontract;
use Modules\Construction\Models\ConstructionTender;
use Modules\Construction\Models\ConstructionVariation;

class ConstructionDashboardService
{
    public function metrics(): array
    {
        $contractValue = (float) ConstructionProjectProfile::sum('contract_value');
        $certified = (float) ConstructionCertificate::sum('gross_certified');
        $invoiced = (float) Invoice::where('industry_module', 'construction')->sum('total');
        $collected = (float) Payment::sum('amount');
        $actualCost = (float) ConstructionMaterialConsumption::sum('cost');
        $committed = (float) ConstructionSubcontract::whereIn('status', ['Approved', 'Active', 'Completed'])->sum('contract_sum');
        $remaining = max(0, $contractValue - $actualCost - $committed);

        return [
            'Active Projects' => ConstructionProjectProfile::whereIn('status', ['Awarded', 'Mobilization', 'Active', 'Near Completion'])->count(),
            'Projects On Schedule' => ConstructionProjectProfile::whereDate('planned_completion', '>=', today())->whereNotIn('status', ['Closed', 'Cancelled'])->count(),
            'Projects Delayed' => ConstructionProjectProfile::whereDate('planned_completion', '<', today())->whereNotIn('status', ['Closed', 'Cancelled'])->count(),
            'Contract Value' => round($contractValue, 2),
            'Certified Value' => round($certified, 2),
            'Invoiced Amount' => round($invoiced, 2),
            'Amount Collected' => round($collected, 2),
            'Outstanding Receivables' => round((float) Invoice::where('balance', '>', 0)->sum('balance'), 2),
            'Project Cost' => round($actualCost, 2),
            'Committed Cost' => round($committed, 2),
            'Remaining Budget' => round($remaining, 2),
            'Gross Project Margin' => $contractValue > 0 ? round((($contractValue - $actualCost) / $contractValue) * 100, 2).'%' : '0%',
            'Open Tenders' => ConstructionTender::whereNotIn('status', ['Awarded', 'Lost', 'Cancelled'])->count(),
            'Pending Variations' => ConstructionVariation::whereIn('status', ['Draft', 'Submitted', 'Under Review'])->count(),
            'Pending Certificates' => ConstructionCertificate::whereIn('status', ['Draft', 'Submitted', 'Consultant Review'])->count(),
            'Material Shortages' => ConstructionMaterial::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'Open Defects' => ConstructionDefect::whereNotIn('status', ['Closed'])->count(),
            'Safety Incidents' => ConstructionSafetyIncident::whereNotIn('status', ['Closed'])->count(),
            'Equipment Utilization' => $this->equipmentUtilization().'%',
        ];
    }

    public function charts(): array
    {
        return [
            'Project Status' => ConstructionProjectProfile::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Budget vs Actual' => ['Budget' => ConstructionProjectProfile::sum('contract_value'), 'Actual Cost' => ConstructionMaterialConsumption::sum('cost')],
            'Revenue vs Cost' => ['Certified Revenue' => ConstructionCertificate::sum('gross_certified'), 'Cost' => ConstructionMaterialConsumption::sum('cost')],
            'Material Consumption' => ConstructionMaterialConsumption::selectRaw('material_name, sum(actual_quantity) as total')->groupBy('material_name')->pluck('total', 'material_name')->all(),
            'Contractor Spend' => ConstructionSubcontract::selectRaw('status, sum(contract_sum) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Tender Conversion' => ConstructionTender::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ];
    }

    public function projectProfitability(): array
    {
        return ConstructionProjectProfile::with('project')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (ConstructionProjectProfile $profile) {
                $cost = (float) ConstructionMaterialConsumption::where('project_id', $profile->project_id)->sum('cost');
                $certified = (float) ConstructionCertificate::where('project_id', $profile->project_id)->sum('gross_certified');
                $margin = $certified > 0 ? (($certified - $cost) / $certified) * 100 : 0;

                return [
                    'project' => $profile->project?->project_name ?? $profile->project_number,
                    'contract' => (float) $profile->contract_value,
                    'certified' => $certified,
                    'cost' => $cost,
                    'margin' => round($margin, 2),
                ];
            })
            ->all();
    }

    public function alerts(): array
    {
        return [
            'Project Delay' => ConstructionProjectProfile::whereDate('planned_completion', '<', today())->whereNotIn('status', ['Closed', 'Cancelled'])->count(),
            'Material Shortage' => ConstructionMaterial::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'Material Request Pending' => ConstructionMaterialRequest::whereIn('status', ['Requested', 'Supervisor Approval', 'Project Manager Approval'])->count(),
            'Variation Awaiting Approval' => ConstructionVariation::whereIn('status', ['Submitted', 'Under Review'])->count(),
            'Certificate Awaiting Approval' => ConstructionCertificate::whereIn('status', ['Submitted', 'Consultant Review'])->count(),
            'Safety Incident' => ConstructionSafetyIncident::whereNotIn('status', ['Closed'])->count(),
            'Defect Overdue' => ConstructionDefect::whereDate('target_date', '<', today())->whereNotIn('status', ['Closed'])->count(),
            'Equipment Maintenance' => ConstructionEquipment::whereDate('next_service_date', '<=', today()->addDays(7))->count(),
        ];
    }

    public function mobileActions(): array
    {
        return [
            'Create Daily Report',
            'Take Site Photo',
            'Request Material',
            'Record Material Usage',
            'Update Progress',
            'Raise RFI',
            'Create Site Instruction',
            'Report Defect',
            'Report Safety Incident',
            'Log Equipment',
        ];
    }

    private function equipmentUtilization(): float
    {
        $total = ConstructionEquipment::count();
        if ($total === 0) {
            return 0.0;
        }

        return round((ConstructionEquipment::whereIn('status', ['Assigned', 'Operating'])->count() / $total) * 100, 2);
    }
}
