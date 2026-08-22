<?php

namespace Modules\PrintingBranding\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\PrintingBranding\Models\Estimate;
use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Models\ProductionOperation;
use Modules\PrintingBranding\Models\QualityCheck;
use Modules\PrintingBranding\Models\Waste;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrintingReportingService
{
    public function reports(): array
    {
        $definition = require base_path('Modules/PrintingBranding/module.php');

        return $definition['reports'] ?? [];
    }

    public function dailyProduction(string|\DateTimeInterface|null $date = null): array
    {
        $day = $this->day($date);
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        $dueJobs = ProductionJob::with('client', 'machine')
            ->whereDate('delivery_date', $day->toDateString())
            ->orderBy('delivery_date')
            ->orderBy('priority')
            ->get();

        $completedJobs = ProductionJob::with('client')
            ->whereBetween('completed_at', [$start, $end])
            ->latest('completed_at')
            ->get();

        $operations = ProductionOperation::with('job.client')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('started_at', [$start, $end])
                    ->orWhereBetween('completed_at', [$start, $end])
                    ->orWhereBetween('created_at', [$start, $end]);
            })
            ->latest()
            ->get();

        $qualityChecks = QualityCheck::with('job')
            ->whereBetween('inspection_date', [$start, $end])
            ->latest('inspection_date')
            ->get();

        $wastes = Waste::with('job')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        $produced = (float) $operations->sum(fn ($operation) => (float) $operation->quantity_produced);
        $rejected = (float) $operations->sum(fn ($operation) => (float) $operation->quantity_rejected)
            + (float) $qualityChecks->sum(fn ($check) => (float) $check->rejected_quantity);
        $wasteQuantity = (float) $wastes->sum(fn ($waste) => (float) $waste->quantity);

        return [
            'date' => $day,
            'metrics' => [
                'Jobs Due' => $dueJobs->count(),
                'Jobs Completed' => $completedJobs->count(),
                'Stages Worked' => $operations->count(),
                'Quantity Produced' => $produced,
                'Quantity Rejected' => $rejected,
                'Waste Quantity' => $wasteQuantity,
                'Waste Cost' => (float) $wastes->sum(fn ($waste) => (float) $waste->cost),
                'Pass Rate' => $this->passRate($qualityChecks),
            ],
            'jobs_due' => $dueJobs,
            'completed_jobs' => $completedJobs,
            'operations' => $operations,
            'quality_checks' => $qualityChecks,
            'wastes' => $wastes,
            'status_breakdown' => $this->countsBy($dueJobs, 'status'),
            'stage_breakdown' => $this->countsBy($operations, 'stage'),
        ];
    }

    public function export(string $type, string|\DateTimeInterface|null $date = null): StreamedResponse
    {
        $rows = match ($type) {
            'jobs' => ProductionJob::with('client')->latest()->get()->map(fn ($job) => [$job->job_number, $job->client?->name, $job->product_name, $job->status, $job->delivery_date?->toDateString()]),
            'estimates' => Estimate::with('client')->latest()->get()->map(fn ($estimate) => [$estimate->estimate_number, $estimate->client?->name, $estimate->product_name, $estimate->status, $estimate->selling_price]),
            'waste' => Waste::latest()->get()->map(fn ($waste) => [$waste->waste_type, $waste->quantity, $waste->cost, $waste->reason]),
            'daily-production' => $this->dailyProductionRows($date),
            default => collect(),
        };

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "printing-{$type}.csv");
    }

    private function dailyProductionRows(string|\DateTimeInterface|null $date): Collection
    {
        $report = $this->dailyProduction($date);

        return collect([['Date', 'Section', 'Reference', 'Client', 'Product/Stage', 'Status', 'Produced', 'Rejected', 'Waste Qty', 'Waste Cost']])
            ->merge($report['jobs_due']->map(fn ($job) => [
                $report['date']->toDateString(),
                'Due Job',
                $job->job_number,
                $job->client?->name,
                $job->product_name,
                $job->status,
                $job->quantity,
                0,
                0,
                0,
            ]))
            ->merge($report['operations']->map(fn ($operation) => [
                $report['date']->toDateString(),
                'Production Stage',
                $operation->job?->job_number,
                $operation->job?->client?->name,
                $operation->stage,
                $operation->status,
                $operation->quantity_produced,
                $operation->quantity_rejected,
                0,
                0,
            ]))
            ->merge($report['wastes']->map(fn ($waste) => [
                $report['date']->toDateString(),
                'Waste',
                $waste->job?->job_number,
                $waste->job?->client?->name,
                $waste->waste_type,
                $waste->reason,
                0,
                0,
                $waste->quantity,
                $waste->cost,
            ]));
    }

    private function day(string|\DateTimeInterface|null $date): Carbon
    {
        return $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
    }

    private function countsBy(Collection $items, string $column): Collection
    {
        return $items
            ->groupBy(fn ($item) => $item->{$column} ?: 'Unspecified')
            ->map(fn ($group) => $group->count())
            ->sortDesc();
    }

    private function passRate(Collection $checks): float
    {
        if ($checks->isEmpty()) {
            return 0.0;
        }

        $passed = $checks->whereIn('result', ['Pass', 'Conditional Pass'])->count();

        return round(($passed / $checks->count()) * 100, 2);
    }
}
