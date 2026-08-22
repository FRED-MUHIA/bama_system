<?php

namespace Modules\Hospitality\Services;

use App\Models\PosOrder;
use App\Models\Product;
use Modules\Hospitality\Models\CheckIn;
use Modules\Hospitality\Models\CheckOut;
use Modules\Hospitality\Models\MaintenanceRequest;
use Modules\Hospitality\Models\Reservation;
use Modules\Hospitality\Models\RestaurantOrder;
use Modules\Hospitality\Models\Room;

class HospitalityDashboardService
{
    public function metrics(): array
    {
        $totalRooms = Room::count();
        $occupied = Room::where('status', 'Occupied')->count();

        return [
            'Occupancy Rate' => $totalRooms ? round(($occupied / $totalRooms) * 100, 1).'%' : '0%',
            'Available Rooms' => Room::where('status', 'Available')->count(),
            "Today's Check-ins" => CheckIn::whereDate('checked_in_at', today())->count(),
            "Today's Check-outs" => CheckOut::whereDate('checked_out_at', today())->count(),
            'Revenue Today' => Reservation::whereDate('created_at', today())->sum('total_amount'),
            'Monthly Revenue' => Reservation::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount'),
            'Pending Reservations' => Reservation::where('status', 'Pending')->count(),
            'Guest Satisfaction' => 'Tracked',
            'Maintenance Requests' => MaintenanceRequest::whereIn('status', ['Open', 'Assigned', 'In Progress'])->count(),
            'Restaurant Sales' => RestaurantOrder::sum('total') ?: PosOrder::whereDate('order_date', today())->where('status', '!=', 'cancelled')->sum('amount_paid'),
            'Low Stock Items' => Product::where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
        ];
    }

    public function executiveKpis(): array
    {
        return [
            'Executive KPIs' => 'Active',
            'Risk Alerts' => MaintenanceRequest::where('priority', 'Critical')->whereNotIn('status', ['Resolved', 'Closed'])->count(),
            'Workflow Performance' => Reservation::whereIn('status', ['Confirmed', 'Checked In'])->count(),
            'Compliance Status' => 'Operational',
        ];
    }
}
