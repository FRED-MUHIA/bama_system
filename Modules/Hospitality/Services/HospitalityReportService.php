<?php

namespace Modules\Hospitality\Services;

use Modules\Hospitality\Models\EventBooking;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\HousekeepingTask;
use Modules\Hospitality\Models\MaintenanceRequest;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\Room;

class HospitalityReportService
{
    public function reports(): array
    {
        $roomCount = Room::count();
        $occupied = Room::where('status', 'Occupied')->count();

        return [
            'Occupancy Reports' => ['rooms' => $roomCount, 'occupied' => $occupied, 'occupancy_rate' => $roomCount ? round($occupied / $roomCount * 100, 1) : 0],
            'Revenue Reports' => ['reservations' => Reservation::sum('total_amount'), 'events' => EventBooking::sum('total_amount'), 'restaurant' => RestaurantOrder::sum('total')],
            'Guest Reports' => ['profiles' => GuestProfile::count(), 'vip' => GuestProfile::where('vip_status', true)->count(), 'blacklisted' => GuestProfile::where('blacklist_flag', true)->count()],
            'Reservation Reports' => Reservation::query()->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status')->all(),
            'Housekeeping Reports' => HousekeepingTask::query()->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status')->all(),
            'Maintenance Reports' => MaintenanceRequest::query()->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status')->all(),
            'Restaurant Reports' => ['orders' => RestaurantOrder::count(), 'sales' => RestaurantOrder::sum('total')],
            'Event Reports' => ['bookings' => EventBooking::count(), 'revenue' => EventBooking::sum('total_amount')],
            'Loyalty Reports' => GuestProfile::query()
                ->selectRaw("COALESCE(loyalty_level, 'None') as level, COUNT(*) as total")
                ->groupByRaw("COALESCE(loyalty_level, 'None')")
                ->pluck('total', 'level')
                ->all(),
        ];
    }
}
