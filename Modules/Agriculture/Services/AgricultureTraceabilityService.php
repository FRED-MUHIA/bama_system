<?php

namespace Modules\Agriculture\Services;

use Modules\Agriculture\Models\ProduceBatch;

class AgricultureTraceabilityService
{
    public function chain(ProduceBatch $batch): array
    {
        $batch->load('farm', 'harvest.field', 'harvest.cropPlan.activities', 'harvest.cropPlan.crop', 'sales.client');
        $harvest = $batch->harvest;
        $plan = $harvest?->cropPlan;

        return [
            'traceability_id' => $batch->traceability_id,
            'farm' => $batch->farm?->name,
            'field' => $harvest?->field?->name,
            'crop_plan' => $plan?->plan_number,
            'crop' => $plan?->crop?->name,
            'activities' => $plan?->activities->pluck('activity_number')->values()->all() ?? [],
            'harvest' => $harvest?->harvest_number,
            'produce_batch' => $batch->batch_number,
            'stage' => $batch->stage,
            'buyers' => $batch->sales->pluck('client.name')->filter()->values()->all(),
        ];
    }
}
