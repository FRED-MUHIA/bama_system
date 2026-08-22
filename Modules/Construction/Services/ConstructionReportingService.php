<?php

namespace Modules\Construction\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Construction\Models\ConstructionBoq;
use Modules\Construction\Models\ConstructionCertificate;
use Modules\Construction\Models\ConstructionDefect;
use Modules\Construction\Models\ConstructionMaterialConsumption;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Models\ConstructionSiteReport;
use Modules\Construction\Models\ConstructionSubcontract;
use Modules\Construction\Models\ConstructionVariation;

class ConstructionReportingService
{
    public function summary(): array
    {
        return [
            'Project Status' => ConstructionProjectProfile::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'BOQ Summary' => ConstructionBoq::selectRaw('status, sum(grand_total) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Material Usage' => ConstructionMaterialConsumption::selectRaw('material_name, sum(cost) as total')->groupBy('material_name')->pluck('total', 'material_name')->all(),
            'Subcontract Value' => ConstructionSubcontract::selectRaw('status, sum(contract_sum) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Certificate Register' => ConstructionCertificate::selectRaw('status, sum(net_certificate) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Variation Register' => ConstructionVariation::selectRaw('status, sum(cost_impact) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Defect Register' => ConstructionDefect::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ];
    }

    public function csv(string $type): StreamedResponse
    {
        $rows = match ($type) {
            'projects' => ConstructionProjectProfile::with('project', 'client')->get()->map(fn ($row) => [
                $row->project_number, $row->project?->project_name, $row->client?->name, $row->status, $row->contract_value,
            ]),
            'boqs' => ConstructionBoq::with('project')->get()->map(fn ($row) => [
                $row->boq_number, $row->title, $row->type, $row->status, $row->grand_total,
            ]),
            'materials' => ConstructionMaterialConsumption::with('project')->get()->map(fn ($row) => [
                $row->usage_date?->toDateString(), $row->project?->project_name, $row->material_name, $row->actual_quantity, $row->cost,
            ]),
            'certificates' => ConstructionCertificate::with('project')->get()->map(fn ($row) => [
                $row->certificate_number, $row->project?->project_name, $row->status, $row->gross_certified, $row->net_certificate,
            ]),
            'site-reports' => ConstructionSiteReport::with('project', 'site')->get()->map(fn ($row) => [
                $row->report_number, $row->report_date?->toDateString(), $row->project?->project_name, $row->site?->name, $row->workforce_count, $row->status,
            ]),
            default => collect(),
        };

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'construction-'.$type.'.csv', ['Content-Type' => 'text/csv']);
    }
}
