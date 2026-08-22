<?php

namespace Modules\Automotive\Services;

use Modules\Automotive\Models\ServiceBooking;

class BookingService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function create(array $data): ServiceBooking
    {
        return ServiceBooking::create([
            ...$data,
            'booking_number' => $data['booking_number'] ?? $this->numbers->next('BK', ServiceBooking::class, 'booking_number'),
        ]);
    }

    public function status(ServiceBooking $booking, string $status): ServiceBooking
    {
        $booking->update(['status' => $status]);

        return $booking->fresh();
    }
}
