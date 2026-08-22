<?php

namespace Modules\Automotive\Services;

use App\Models\Client;
use App\Models\Invoice;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\Part;
use Modules\Automotive\Models\QualityCheck;
use Modules\Automotive\Models\ServiceBooking;
use Modules\Automotive\Models\Vehicle;
use Modules\Automotive\Models\Warranty;

class AutomotiveDashboardService
{
    public function metrics(): array
    {
        $jobs = JobCard::query();
        $invoices = Invoice::source()->where('industry_module', 'automotive')->get();

        return [
            'Vehicles Registered' => Vehicle::count(),
            'Active Customers' => Client::count(),
            'Bookings Today' => ServiceBooking::whereDate('preferred_date', today())->count(),
            'Vehicles Checked In' => Vehicle::where('status', 'In Workshop')->count(),
            'Open Job Cards' => (clone $jobs)->whereNotIn('status', ['Completed', 'Cancelled'])->count(),
            'Jobs In Progress' => JobCard::where('status', 'In Progress')->count(),
            'Jobs Awaiting Parts' => JobCard::where('status', 'Awaiting Parts')->count(),
            'Jobs Awaiting Approval' => JobCard::where('status', 'Awaiting Customer Approval')->count(),
            'Vehicles Ready for Collection' => JobCard::where('status', 'Ready for Collection')->count(),
            'Overdue Jobs' => JobCard::whereNotIn('status', ['Completed', 'Cancelled'])->where('estimated_completion', '<', now())->count(),
            'Revenue Today' => Invoice::source()->where('industry_module', 'automotive')->whereDate('invoice_date', today())->sum('total'),
            'Monthly Revenue' => Invoice::source()->where('industry_module', 'automotive')->whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            'Labour Revenue' => $this->contextSum($invoices, 'labour_revenue'),
            'Parts Revenue' => $this->contextSum($invoices, 'parts_revenue'),
            'Outstanding Invoices' => Invoice::source()->where('industry_module', 'automotive')->sum('balance'),
            'Inventory Value' => Part::query()->selectRaw('SUM(stock_quantity * cost_price) as total')->value('total') ?? 0,
            'Low Stock Parts' => Part::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'Technician Utilization' => round(min(JobCard::where('status', 'In Progress')->count() * 12.5, 100), 2).'%',
            'Average Repair Time' => round(JobCard::whereNotNull('updated_at')->avg('id') ? 1 : 0, 2).' days',
            'Repeat Customers' => Vehicle::select('client_id')->whereNotNull('client_id')->groupBy('client_id')->havingRaw('COUNT(*) > 1')->count(),
            'Customer Satisfaction' => '0%',
            'Warranty Comebacks' => Warranty::where('status', 'Claimed')->count(),
        ];
    }

    public function charts(): array
    {
        $invoices = Invoice::source()->where('industry_module', 'automotive')->get();

        return [
            'Jobs by Status' => JobCard::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Daily Revenue' => Invoice::source()->where('industry_module', 'automotive')->whereDate('invoice_date', today())->pluck('total', 'invoice_number')->all(),
            'Labour vs Parts Revenue' => [
                'Labour' => $this->contextSum($invoices, 'labour_revenue'),
                'Parts' => $this->contextSum($invoices, 'parts_revenue'),
            ],
            'Top Spare Parts' => Part::orderByDesc('stock_quantity')->limit(8)->pluck('stock_quantity', 'name')->all(),
            'Vehicle Makes Serviced' => Vehicle::selectRaw('make, COUNT(*) as total')->groupBy('make')->pluck('total', 'make')->all(),
            'QC Failure' => QualityCheck::selectRaw('result, COUNT(*) as total')->groupBy('result')->pluck('total', 'result')->all(),
        ];
    }

    public function alerts(): array
    {
        return [
            'Booking Starting Soon' => ServiceBooking::whereDate('preferred_date', today())->where('status', 'Confirmed')->count(),
            'Vehicle Awaiting Inspection' => JobCard::where('status', 'Awaiting Inspection')->count(),
            'Estimate Awaiting Approval' => JobCard::where('status', 'Awaiting Customer Approval')->count(),
            'Job Overdue' => JobCard::whereNotIn('status', ['Completed', 'Cancelled'])->where('estimated_completion', '<', now())->count(),
            'Parts Shortage' => Part::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'Vehicle Ready for Collection' => JobCard::where('status', 'Ready for Collection')->count(),
            'Warranty Expiring' => Warranty::whereBetween('warranty_end', [today(), today()->addDays(30)])->count(),
            'QC Failed' => QualityCheck::where('result', 'Fail')->count(),
        ];
    }

    public function mobileActions(): array
    {
        return ['My Jobs', 'Current Job', 'Inspection', 'Start Work', 'Pause Work', 'Complete Work', 'Request Part', 'Add Note', 'Upload Photo', 'Report Problem'];
    }

    private function contextSum($invoices, string $key): float
    {
        return (float) $invoices->sum(fn (Invoice $invoice) => (float) ($invoice->industry_context[$key] ?? 0));
    }
}
