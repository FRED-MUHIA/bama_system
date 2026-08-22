<?php

namespace Modules\Automotive\Services;

use Modules\Automotive\Models\CheckIn;
use Modules\Automotive\Models\Vehicle;

class VehicleCheckInService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function create(array $data): CheckIn
    {
        return $this->numbers->transaction(function () use ($data) {
            $checkIn = CheckIn::create([
                ...$data,
                'check_in_number' => $data['check_in_number'] ?? $this->numbers->next('CHK', CheckIn::class, 'check_in_number'),
                'checked_in_at' => $data['checked_in_at'] ?? now(),
            ]);

            Vehicle::whereKey($checkIn->vehicle_id)->update([
                'status' => 'In Workshop',
                'mileage' => $data['mileage'] ?? Vehicle::whereKey($checkIn->vehicle_id)->value('mileage'),
            ]);

            $checkIn->booking?->update(['status' => 'Checked In']);

            return $checkIn;
        });
    }
}
