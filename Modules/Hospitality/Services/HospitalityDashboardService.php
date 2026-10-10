<?php

namespace Modules\Hospitality\Services;

use App\Models\Invoice;
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
            'Revenue Today' => $this->recognizedRevenue(today()->startOfDay(), today()->endOfDay()),
            'Monthly Revenue' => $this->recognizedRevenue(now()->startOfMonth(), now()->endOfMonth()),
            'Pending Reservations' => Reservation::where('status', 'Pending')->count(),
            'Guest Satisfaction' => 'Tracked',
            'Maintenance Requests' => MaintenanceRequest::whereIn('status', ['Open', 'Assigned', 'In Progress'])->count(),
            'Restaurant Sales' => round((float) app(\App\Services\FinanceRecordSyncService::class)->invoices()
                ->filter(fn ($invoice) => $invoice->industry_module === 'hospitality' && str_starts_with((string) $invoice->industry_reference, 'restaurant'))
                ->sum('total'), 2),
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

    private function recognizedRevenue($start, $end): float
    {
        return round((float) Invoice::query()
            ->where('industry_module', 'hospitality')
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total'), 2);
    }
}
