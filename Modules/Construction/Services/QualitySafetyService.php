<?php

namespace Modules\Construction\Services;

use Modules\Construction\Models\ConstructionDefect;
use Modules\Construction\Models\ConstructionQualityInspection;
use Modules\Construction\Models\ConstructionSafetyIncident;

class QualitySafetyService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function inspection(array $data): ConstructionQualityInspection
    {
        return ConstructionQualityInspection::create([
            ...$data,
            'inspection_number' => $data['inspection_number'] ?? $this->numbers->next('IR', ConstructionQualityInspection::class, 'inspection_number'),
        ]);
    }

    public function incident(array $data): ConstructionSafetyIncident
    {
        return ConstructionSafetyIncident::create([
            ...$data,
            'incident_number' => $data['incident_number'] ?? $this->numbers->next('INC', ConstructionSafetyIncident::class, 'incident_number'),
        ]);
    }

    public function defect(array $data): ConstructionDefect
    {
        return ConstructionDefect::create([
            ...$data,
            'defect_number' => $data['defect_number'] ?? $this->numbers->next('DEF', ConstructionDefect::class, 'defect_number'),
        ]);
    }
}
