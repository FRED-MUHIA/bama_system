<?php

namespace Modules\Automotive\Services;

use App\Support\ActiveTenant;
use Illuminate\Validation\ValidationException;
use Modules\Automotive\Models\ServiceReminder;
use Modules\Automotive\Models\Vehicle;

class VehicleService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function create(array $data): Vehicle
    {
        $this->guardDuplicate($data);

        return Vehicle::create($data);
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $this->guardDuplicate($data, $vehicle);
        $vehicle->update($data);

        return $vehicle->fresh();
    }

    public function serviceHistory(Vehicle $vehicle): array
    {
        return [
            'bookings' => $vehicle->bookings()->latest()->get(),
            'check_ins' => $vehicle->checkIns()->latest()->get(),
            'inspections' => $vehicle->inspections()->with('items')->latest()->get(),
            'job_cards' => $vehicle->jobCards()->with('invoice', 'technician')->latest()->get(),
            'releases' => $vehicle->releases()->latest()->get(),
            'warranties' => $vehicle->warranties()->latest()->get(),
            'reminders' => $vehicle->reminders()->latest()->get(),
        ];
    }

    public function reminder(Vehicle $vehicle, array $data): ServiceReminder
    {
        return ServiceReminder::create([
            ...$data,
            'vehicle_id' => $vehicle->id,
            'reminder_number' => $data['reminder_number'] ?? $this->numbers->next('SRV', ServiceReminder::class, 'reminder_number'),
        ]);
    }

    private function guardDuplicate(array $data, ?Vehicle $ignore = null): void
    {
        $tenantId = ActiveTenant::id();
        $query = Vehicle::withoutGlobalScopes()->where('tenant_id', $tenantId);

        if (! empty($data['registration_number'])) {
            $exists = (clone $query)
                ->where('registration_number', $data['registration_number'])
                ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages(['registration_number' => 'Vehicle registration already exists for this tenant.']);
            }
        }

        if (! empty($data['vin'])) {
            $exists = (clone $query)
                ->where('vin', $data['vin'])
                ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages(['vin' => 'Vehicle VIN already exists for this tenant.']);
            }
        }
    }
}
