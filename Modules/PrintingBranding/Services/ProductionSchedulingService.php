<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Models\ProductionSchedule;

class ProductionSchedulingService
{
    public function schedule(ProductionJob $job, array $data): ProductionSchedule
    {
        $starts = $data['starts_at'];
        $ends = $data['ends_at'];

        if (! empty($data['machine_id'])) {
            $conflict = ProductionSchedule::where('machine_id', $data['machine_id'])
                ->where('status', '!=', 'Cancelled')
                ->where('starts_at', '<', $ends)
                ->where('ends_at', '>', $starts)
                ->exists();

            abort_if($conflict, 422, 'Machine is already booked for that time window.');
        }

        $schedule = ProductionSchedule::create($data + ['job_id' => $job->id]);

        if (! in_array($job->status, ['In Production', 'Printing', 'Finishing', 'Quality Control', 'Ready for Dispatch', 'Dispatched', 'Completed', 'Cancelled'], true)) {
            $job->update(['status' => 'Queued']);
        }

        return $schedule;
    }
}
