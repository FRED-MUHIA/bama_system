<?php

namespace Modules\Automotive\Services;

use Modules\Automotive\Models\Fleet;

class FleetService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function create(array $data): Fleet
    {
        return Fleet::create([
            ...$data,
            'fleet_number' => $data['fleet_number'] ?? $this->numbers->next('FLT', Fleet::class, 'fleet_number'),
        ]);
    }

    public function dashboard(Fleet $fleet): array
    {
        $vehicles = $fleet->vehicles()->with('jobCards')->get();

        return [
            'total_vehicles' => $vehicles->count(),
            'vehicles_due_service' => $vehicles->filter(fn ($vehicle) => $vehicle->next_service_date && $vehicle->next_service_date->lte(today()->addDays(14)))->count(),
            'vehicles_in_workshop' => $vehicles->where('status', 'In Workshop')->count(),
            'maintenance_spend' => $vehicles->sum(fn ($vehicle) => $vehicle->jobCards->sum(fn ($job) => $job->invoice?->total ?? 0)),
        ];
    }
}
