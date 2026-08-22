<?php

namespace Modules\Automotive\Services;

use Modules\Automotive\Models\Comeback;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\Warranty;

class WarrantyService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function warranty(array $data): Warranty
    {
        return Warranty::create([
            ...$data,
            'warranty_number' => $data['warranty_number'] ?? $this->numbers->next('WRN', Warranty::class, 'warranty_number'),
        ]);
    }

    public function comeback(JobCard $job, array $data): Comeback
    {
        return Comeback::create([
            ...$data,
            'original_job_card_id' => $job->id,
            'vehicle_id' => $job->vehicle_id,
            'comeback_number' => $data['comeback_number'] ?? $this->numbers->next('CMP', Comeback::class, 'comeback_number'),
            'return_date' => $data['return_date'] ?? today(),
        ]);
    }
}
