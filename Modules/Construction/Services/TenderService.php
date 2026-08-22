<?php

namespace Modules\Construction\Services;

use App\Models\Project;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Models\ConstructionTender;

class TenderService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function create(array $data): ConstructionTender
    {
        return ConstructionTender::create([
            ...$data,
            'tender_number' => $data['tender_number'] ?? $this->numbers->next('TND', ConstructionTender::class, 'tender_number'),
        ]);
    }

    public function convertToProject(ConstructionTender $tender): ConstructionProjectProfile
    {
        return $this->numbers->transaction(function () use ($tender) {
            $project = Project::create([
                'client_id' => $tender->client_id,
                'project_name' => $tender->name,
                'status' => 'Approved',
                'scope' => $tender->requirements,
            ]);

            $profile = ConstructionProjectProfile::create([
                'project_id' => $project->id,
                'client_id' => $tender->client_id,
                'project_number' => $this->numbers->next('CON', ConstructionProjectProfile::class, 'project_number'),
                'contract_value' => $tender->tender_value,
                'status' => 'Awarded',
            ]);

            $tender->update(['project_id' => $project->id, 'status' => 'Awarded']);

            return $profile;
        });
    }
}
