<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IndustrySetupService;
use Modules\Hospitality\Models\EventBooking;
use Modules\Hospitality\Models\GuestProfile;
use Modules\Hospitality\Models\HousekeepingTask;
use Modules\Hospitality\Models\MaintenanceRequest;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\Room;
use Modules\Hospitality\Services\HospitalityDashboardService;

class HospitalityDashboardController extends Controller
{
    public function __invoke(HospitalityDashboardService $dashboard)
    {
        $tenant = auth()->user()?->currentTenant;

        return view('hospitality.dashboard', [
            'tenant' => $tenant,
            'industryDashboard' => app(IndustrySetupService::class)->dashboardFeaturesForTenant($tenant),
            'metrics' => $dashboard->metrics(),
            'kpis' => $dashboard->executiveKpis(),
            'moduleBadges' => [
                ['label' => 'Hotel Reservations', 'route' => 'hospitality.reservations.index'],
                ['label' => 'Room Management', 'route' => 'hospitality.rooms.index'],
                ['label' => 'Products / Stock', 'route' => 'products.index'],
                ['label' => 'Housekeeping', 'route' => 'hospitality.housekeeping.index'],
                ['label' => 'Front Desk', 'route' => 'hospitality.front-desk.index'],
                ['label' => 'Guest Profiles', 'route' => 'hospitality.guests.index'],
                ['label' => 'Restaurant POS', 'route' => 'hospitality.restaurant.index'],
                ['label' => 'Procurement', 'route' => 'erp.procurement'],
                ['label' => 'Event Booking', 'route' => 'hospitality.events.index'],
                ['label' => 'Billing', 'route' => 'finance.index'],
                ['label' => 'Customer Loyalty', 'route' => 'hospitality.guests.index'],
            ],
            'rooms' => Room::orderBy('floor')->orderBy('room_number')->limit(80)->get(),
            'reservations' => Reservation::with('guestProfile', 'room')->latest()->limit(8)->get(),
            'housekeeping' => HousekeepingTask::with('room')->whereIn('status', ['Pending', 'Assigned', 'In Progress'])->latest()->limit(8)->get(),
            'maintenance' => MaintenanceRequest::with('room')->whereIn('status', ['Open', 'Assigned', 'In Progress'])->latest()->limit(8)->get(),
            'restaurantOrders' => RestaurantOrder::latest()->limit(5)->get(),
            'events' => EventBooking::latest()->limit(5)->get(),
            'guestCount' => GuestProfile::count(),
        ]);
    }
}
