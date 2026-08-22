<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\JobCost;
use Modules\PrintingBranding\Models\ProductionJob;

class PrintCostingService
{
    public function calculate(ProductionJob $job, array $data = []): JobCost
    {
        $estimated = (float) ($data['estimated_material_cost'] ?? $job->estimate?->total_cost ?? 0);
        $actualMaterial = (float) ($data['actual_material_cost'] ?? $job->reservations()->with('material')->get()->sum(fn ($reservation) => (float) $reservation->consumed_quantity * (float) $reservation->material?->unit_cost));
        $total = $actualMaterial
            + (float) ($data['machine_cost'] ?? 0)
            + (float) ($data['labor_cost'] ?? 0)
            + (float) ($data['artwork_cost'] ?? 0)
            + (float) ($data['finishing_cost'] ?? 0)
            + (float) ($data['outsourcing_cost'] ?? 0)
            + (float) ($data['transport_cost'] ?? 0)
            + (float) ($data['overhead_allocation'] ?? 0);
        $selling = (float) ($data['selling_price'] ?? $job->quotation?->total ?? $job->estimate?->selling_price ?? 0);
        $profit = $selling - $total;

        return JobCost::updateOrCreate(
            ['job_id' => $job->id],
            $data + [
                'estimated_material_cost' => $estimated,
                'actual_material_cost' => $actualMaterial,
                'total_cost' => round($total, 2),
                'selling_price' => round($selling, 2),
                'gross_profit' => round($profit, 2),
                'margin_percent' => $selling > 0 ? round(($profit / $selling) * 100, 2) : 0,
                'variance' => round($actualMaterial - $estimated, 2),
            ]
        );
    }
}
