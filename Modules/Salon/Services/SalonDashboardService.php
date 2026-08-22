<?php

namespace Modules\Salon\Services;

use App\Models\Payment;
use App\Models\Product;
use Modules\Salon\Models\Appointment;
use Modules\Salon\Models\ClientProfile;
use Modules\Salon\Models\Commission;
use Modules\Salon\Models\Membership;
use Modules\Salon\Models\ProductConsumption;
use Modules\Salon\Models\Service;
use Modules\Salon\Models\StaffProfile;

class SalonDashboardService
{
    public function metrics(): array
    {
        $todayAppointments = Appointment::whereDate('starts_at', today());
        $monthAppointments = Appointment::whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()]);

        return [
            'Appointments Today' => (clone $todayAppointments)->count(),
            'Confirmed Today' => (clone $todayAppointments)->whereIn('status', ['Booked', 'Confirmed', 'Arrived'])->count(),
            'Revenue MTD' => (float) (clone $monthAppointments)->whereIn('status', ['Completed', 'Paid'])->sum('total'),
            'Active Clients' => ClientProfile::where('status', 'Active')->count(),
            'Active Staff' => StaffProfile::where('status', 'Active')->count(),
            'Services' => Service::where('is_active', true)->count(),
            'Active Memberships' => Membership::where('status', 'Active')->count(),
            'Product Consumption MTD' => (float) ProductConsumption::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_cost'),
            'Commission Payable' => (float) Commission::where('status', 'Pending')->sum('amount'),
            'Low Stock Items' => Product::where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'Payments MTD' => (float) Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->where('payable_type', 'like', '%Salon%')->sum('amount'),
        ];
    }

    public function kpis(): array
    {
        $scheduledMinutes = Appointment::whereDate('starts_at', today())
            ->get(['starts_at', 'ends_at'])
            ->sum(fn (Appointment $appointment) => $appointment->ends_at ? $appointment->starts_at->diffInMinutes($appointment->ends_at) : 0);
        $capacityMinutes = StaffProfile::where('status', 'Active')->count() * 480;

        return [
            'Staff Utilization' => $capacityMinutes ? round(($scheduledMinutes / $capacityMinutes) * 100, 1).'%' : '0%',
            'Repeat Clients' => ClientProfile::where('lifetime_visits', '>', 1)->count(),
            'No-show Risk' => Appointment::whereDate('starts_at', today())->where('status', 'No Show')->count(),
            'Retail Attach Rate' => 'Tracked via POS',
        ];
    }

    public function reports(): array
    {
        return [
            'Appointment utilization',
            'Service revenue by category',
            'Staff commission and productivity',
            'Membership retention',
            'Gift card liability',
            'Product consumption and margin',
            'Client loyalty and repeat visits',
            'Multi-branch operating summary',
        ];
    }
}
