<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Models\QualityCheck;
use Modules\PrintingBranding\Models\Reprint;

class QualityControlService
{
    public function inspect(ProductionJob $job, array $data): QualityCheck
    {
        $check = QualityCheck::create($data + [
            'job_id' => $job->id,
            'inspection_date' => $data['inspection_date'] ?? now(),
        ]);

        $job->update(['status' => in_array($check->result, ['Reject', 'Reprint Required'], true) ? 'On Hold' : 'Ready for Dispatch']);

        return $check;
    }

    public function reprint(ProductionJob $job, array $data, PrintingNumberService $numbers): Reprint
    {
        return Reprint::create($data + [
            'original_job_id' => $job->id,
            'reprint_number' => $data['reprint_number'] ?? $numbers->next('RPT', Reprint::class, 'reprint_number'),
        ]);
    }
}
