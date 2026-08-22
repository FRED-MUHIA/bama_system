<?php

namespace Modules\Automotive\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Automotive\Models\Estimate;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\JobCost;
use Modules\Automotive\Models\Part;
use Modules\Automotive\Models\ServiceReminder;
use Modules\Automotive\Models\SpecialtyRecord;
use Modules\Automotive\Models\Vehicle;

class AutomotiveReportingService
{
    public function summary(): array
    {
        return [
            'workshop' => JobCard::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'parts' => Part::selectRaw('category, SUM(stock_quantity) as total')->groupBy('category')->pluck('total', 'category')->all(),
            'vehicles' => Vehicle::selectRaw('make, COUNT(*) as total')->groupBy('make')->pluck('total', 'make')->all(),
            'estimates' => Estimate::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'job_costing' => [
                'revenue' => JobCost::sum('revenue'),
                'actual_cost' => JobCost::sum('actual_cost'),
                'gross_profit' => JobCost::sum('gross_profit'),
            ],
            'service_reminders' => ServiceReminder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'specialty_records' => SpecialtyRecord::selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type')->all(),
        ];
    }

    public function csv(string $type): StreamedResponse
    {
        $rows = match ($type) {
            'vehicles' => Vehicle::latest()->get(['registration_number', 'make', 'model', 'status', 'mileage']),
            'estimates' => Estimate::with('vehicle', 'client')->latest()->get()->map(fn ($estimate) => [
                'estimate_number' => $estimate->estimate_number,
                'vehicle' => $estimate->vehicle?->registration_number,
                'client' => $estimate->client?->name,
                'status' => $estimate->status,
                'total' => $estimate->total,
            ]),
            'job-cards' => JobCard::with('vehicle', 'client')->latest()->get()->map(fn ($job) => [
                'job_number' => $job->job_number,
                'vehicle' => $job->vehicle?->registration_number,
                'client' => $job->client?->name,
                'status' => $job->status,
                'priority' => $job->priority,
            ]),
            'parts' => Part::latest()->get(['part_number', 'name', 'category', 'stock_quantity', 'reorder_level']),
            'job-costing' => JobCost::with('jobCard.vehicle')->latest()->get()->map(fn ($cost) => [
                'job_number' => $cost->jobCard?->job_number,
                'vehicle' => $cost->jobCard?->vehicle?->registration_number,
                'actual_cost' => $cost->actual_cost,
                'revenue' => $cost->revenue,
                'gross_profit' => $cost->gross_profit,
                'margin_percentage' => $cost->margin_percentage,
            ]),
            'service-reminders' => ServiceReminder::with('vehicle')->latest()->get()->map(fn ($reminder) => [
                'reminder_number' => $reminder->reminder_number,
                'vehicle' => $reminder->vehicle?->registration_number,
                'type' => $reminder->type,
                'due_date' => $reminder->due_date?->toDateString(),
                'status' => $reminder->status,
            ]),
            'specialty' => SpecialtyRecord::with('vehicle', 'jobCard')->latest()->get()->map(fn ($record) => [
                'record_number' => $record->record_number,
                'type' => $record->type,
                'vehicle' => $record->vehicle?->registration_number,
                'job_number' => $record->jobCard?->job_number,
                'status' => $record->status,
            ]),
            default => collect(),
        };

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            $first = $rows->first();
            if ($first) {
                $first = is_array($first) ? $first : $first->toArray();
                fputcsv($out, array_keys($first));
                foreach ($rows as $row) {
                    fputcsv($out, array_values(is_array($row) ? $row : $row->toArray()));
                }
            }
            fclose($out);
        }, 'automotive-report.csv');
    }
}
