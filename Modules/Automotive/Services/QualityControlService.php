<?php

namespace Modules\Automotive\Services;

use Illuminate\Validation\ValidationException;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\QualityCheck;
use Modules\Automotive\Models\RoadTest;
use Modules\Automotive\Models\VehicleRelease;

class QualityControlService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function quality(JobCard $job, array $data): QualityCheck
    {
        $qc = QualityCheck::create([
            ...$data,
            'job_card_id' => $job->id,
            'vehicle_id' => $job->vehicle_id,
            'qc_number' => $data['qc_number'] ?? $this->numbers->next('QC', QualityCheck::class, 'qc_number'),
            'inspected_at' => $data['inspected_at'] ?? now(),
        ]);

        $job->update(['status' => $qc->result === 'Fail' ? 'In Progress' : 'Quality Check']);

        return $qc;
    }

    public function roadTest(JobCard $job, array $data): RoadTest
    {
        $distance = isset($data['start_mileage'], $data['end_mileage'])
            ? max((int) $data['end_mileage'] - (int) $data['start_mileage'], 0)
            : (float) ($data['distance'] ?? 0);

        return RoadTest::create([
            ...$data,
            'job_card_id' => $job->id,
            'vehicle_id' => $job->vehicle_id,
            'road_test_number' => $data['road_test_number'] ?? $this->numbers->next('RT', RoadTest::class, 'road_test_number'),
            'distance' => $distance,
        ]);
    }

    public function release(JobCard $job, array $data): VehicleRelease
    {
        if (($data['payment_status'] ?? $job->invoice?->payment_status) !== 'paid' && empty($data['override_unpaid'])) {
            throw ValidationException::withMessages(['payment_status' => 'Vehicle release requires paid invoice or authorized override.']);
        }

        $release = VehicleRelease::create([
            ...$data,
            'job_card_id' => $job->id,
            'vehicle_id' => $job->vehicle_id,
            'invoice_id' => $data['invoice_id'] ?? $job->invoice_id,
            'release_number' => $data['release_number'] ?? $this->numbers->next('REL', VehicleRelease::class, 'release_number'),
            'released_at' => $data['released_at'] ?? now(),
        ]);

        $job->update(['status' => 'Completed']);
        $job->vehicle()->update(['status' => 'Active', 'mileage' => $data['final_mileage'] ?? $job->vehicle?->mileage]);

        return $release;
    }
}
