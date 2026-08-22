<?php

namespace Modules\Construction\Services;

use App\Models\Project;
use Modules\Construction\Models\ConstructionProjectProfile;
use Modules\Construction\Models\ConstructionSite;

class ConstructionService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function createProject(array $data): ConstructionProjectProfile
    {
        return $this->numbers->transaction(function () use ($data) {
            $project = Project::create([
                'client_id' => $data['client_id'] ?? null,
                'project_name' => $data['project_name'],
                'status' => $data['status'] ?? 'Tendering',
                'scope' => $data['scope'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return ConstructionProjectProfile::create([
                'project_id' => $project->id,
                'client_id' => $data['client_id'] ?? null,
                'project_manager_id' => $data['project_manager_id'] ?? null,
                'site_manager_id' => $data['site_manager_id'] ?? null,
                'project_number' => $this->numbers->next('CON', ConstructionProjectProfile::class, 'project_number'),
                'contract_type' => $data['contract_type'] ?? null,
                'contract_value' => $data['contract_value'] ?? 0,
                'start_date' => $data['start_date'] ?? null,
                'planned_completion' => $data['planned_completion'] ?? null,
                'location' => $data['location'] ?? null,
                'retention_percentage' => $data['retention_percentage'] ?? 0,
                'defects_liability_days' => $data['defects_liability_days'] ?? 0,
                'status' => $data['status'] ?? 'Tendering',
                'meta' => [
                    'consultant' => $data['consultant'] ?? null,
                    'architect' => $data['architect'] ?? null,
                    'engineer' => $data['engineer'] ?? null,
                    'quantity_surveyor' => $data['quantity_surveyor'] ?? null,
                    'main_contractor' => $data['main_contractor'] ?? null,
                ],
            ]);
        });
    }

    public function createSite(array $data): ConstructionSite
    {
        return ConstructionSite::create($data);
    }
}
