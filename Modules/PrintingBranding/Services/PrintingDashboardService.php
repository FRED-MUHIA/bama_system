<?php

namespace Modules\PrintingBranding\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Product;
use Modules\PrintingBranding\Models\Artwork;
use Modules\PrintingBranding\Models\Machine;
use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Models\Waste;

class PrintingDashboardService
{
    public function metrics(): array
    {
        $revenueToday = Invoice::whereDate('invoice_date', today())->sum('total');
        $monthlyRevenue = Invoice::whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total');
        $wasteCost = Waste::sum('cost');
        $cost = ProductionJob::with('cost')->get()->sum(fn ($job) => (float) $job->cost?->total_cost);
        $selling = ProductionJob::with('cost')->get()->sum(fn ($job) => (float) $job->cost?->selling_price);

        return [
            'New Leads' => 0,
            'Quotations Pending' => Quotation::whereIn('status', ['draft', 'sent', 'pending'])->count(),
            'Quotes Approved' => Quotation::whereIn('status', ['approved', 'converted'])->count(),
            'Jobs Open' => ProductionJob::whereNotIn('status', ['Completed', 'Cancelled'])->count(),
            'Jobs In Production' => ProductionJob::whereIn('status', ['In Production', 'Printing', 'Finishing', 'Quality Control'])->count(),
            'Jobs Awaiting Artwork' => ProductionJob::where('status', 'Awaiting Artwork')->count(),
            'Jobs Awaiting Client Approval' => ProductionJob::where('status', 'Awaiting Approval')->count(),
            'Jobs Ready for Dispatch' => ProductionJob::where('status', 'Ready for Dispatch')->count(),
            'Jobs Overdue' => ProductionJob::whereDate('delivery_date', '<', today())->whereNotIn('status', ['Completed', 'Cancelled'])->count(),
            'Jobs Completed Today' => ProductionJob::whereDate('completed_at', today())->count(),
            'Revenue Today' => round((float) $revenueToday, 2),
            'Monthly Revenue' => round((float) $monthlyRevenue, 2),
            'Gross Margin' => $selling > 0 ? round((($selling - $cost) / $selling) * 100, 2).'%' : '0%',
            'Outstanding Invoices' => Invoice::where('balance', '>', 0)->count(),
            'Stock Alerts' => Product::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'Machines Under Maintenance' => Machine::whereIn('status', ['Maintenance', 'Breakdown'])->count(),
            'Production Efficiency' => $this->productionEfficiency().'%',
            'Waste Percentage' => $this->wastePercentage().'%',
            'Waste Cost' => round((float) $wasteCost, 2),
        ];
    }

    public function charts(): array
    {
        return [
            'Jobs by Status' => ProductionJob::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Machine Utilization' => Machine::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'Waste Trends' => Waste::selectRaw('waste_type, sum(cost) as total')->groupBy('waste_type')->pluck('total', 'waste_type')->all(),
            'Artwork Status' => Artwork::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ];
    }

    public function board(): array
    {
        $columns = [
            'New Jobs' => ['Draft'],
            'Artwork' => ['Awaiting Artwork', 'Artwork In Progress'],
            'Awaiting Approval' => ['Awaiting Approval'],
            'Ready for Production' => ['Approved', 'Queued'],
            'Printing' => ['In Production', 'Printing'],
            'Finishing' => ['Finishing'],
            'Quality Control' => ['Quality Control'],
            'Ready for Dispatch' => ['Ready for Dispatch'],
            'Completed' => ['Completed'],
        ];

        return collect($columns)->mapWithKeys(fn ($statuses, $column) => [
            $column => ProductionJob::with('client', 'machine')->whereIn('status', $statuses)->orderBy('delivery_date')->get(),
        ])->all();
    }

    private function productionEfficiency(): float
    {
        $total = ProductionJob::whereNotIn('status', ['Draft', 'Cancelled'])->count();

        return $total > 0 ? round((ProductionJob::where('status', 'Completed')->count() / $total) * 100, 2) : 0.0;
    }

    private function wastePercentage(): float
    {
        $waste = Waste::sum('quantity');
        $produced = \Modules\PrintingBranding\Models\ProductionOperation::sum('quantity_produced');

        return app(WasteService::class)->percentage((float) $waste, (float) $produced);
    }
}
