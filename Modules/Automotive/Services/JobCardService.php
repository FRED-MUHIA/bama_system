<?php

namespace Modules\Automotive\Services;

use Illuminate\Validation\ValidationException;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\LabourTask;
use Modules\Automotive\Models\Vehicle;
use Modules\Automotive\Models\WorkshopBay;

class JobCardService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function create(array $data): JobCard
    {
        return $this->numbers->transaction(function () use ($data) {
            $job = JobCard::create([
                ...$data,
                'job_number' => $data['job_number'] ?? $this->numbers->next('JC', JobCard::class, 'job_number'),
            ]);

            Vehicle::whereKey($job->vehicle_id)->update(['status' => $this->vehicleStatusFor($job->status)]);
            $job->booking?->update(['status' => 'Converted to Job']);
            $job->checkIn?->update(['status' => 'Converted to Job']);
            if ($job->workshop_bay_id) {
                WorkshopBay::whereKey($job->workshop_bay_id)->update(['status' => 'Occupied', 'assigned_job_card_id' => $job->id]);
            }

            return $job;
        });
    }

    public function updateStatus(JobCard $job, string $status): JobCard
    {
        if ($status === 'Ready for Collection') {
            $this->ensureReadyForCollection($job);
        }

        $job->update(['status' => $status]);
        $job->vehicle()->update(['status' => $this->vehicleStatusFor($status)]);

        return $job->fresh();
    }

    public function assign(JobCard $job, ?int $technicianId = null, ?int $bayId = null): JobCard
    {
        $job->update(['technician_id' => $technicianId ?? $job->technician_id, 'workshop_bay_id' => $bayId ?? $job->workshop_bay_id]);

        if ($bayId) {
            WorkshopBay::whereKey($bayId)->update(['status' => 'Occupied', 'assigned_job_card_id' => $job->id, 'assigned_technician_id' => $technicianId]);
        }

        return $job->fresh();
    }

    public function addLabourTask(JobCard $job, array $data): LabourTask
    {
        $hours = (float) ($data['billable_hours'] ?? $data['standard_hours'] ?? 0);
        $rate = (float) ($data['hourly_rate'] ?? 0);

        return LabourTask::create([
            ...$data,
            'job_card_id' => $job->id,
            'line_total' => round($hours * $rate, 2),
        ]);
    }

    public function taskStatus(LabourTask $task, string $status): LabourTask
    {
        $updates = ['status' => $status];
        if ($status === 'In Progress' && ! $task->started_at) {
            $updates['started_at'] = now();
        }
        if ($status === 'Paused') {
            $updates['paused_at'] = now();
        }
        if ($status === 'Completed') {
            $updates['completed_at'] = now();
            $updates['actual_hours'] = $task->actual_hours ?: $task->billable_hours;
        }
        $task->update($updates);

        return $task->fresh();
    }

    private function ensureReadyForCollection(JobCard $job): void
    {
        $qcPassed = $job->qualityChecks()->whereIn('result', ['Pass', 'Conditional Pass'])->exists();
        $roadTestPassed = ! $job->roadTests()->exists() || $job->roadTests()->whereIn('test_result', ['Passed', 'Not Required'])->exists();

        if (! $qcPassed || ! $roadTestPassed || ! $job->invoice_id) {
            throw ValidationException::withMessages(['status' => 'QC, road test, and invoice must be complete before vehicle release.']);
        }
    }

    private function vehicleStatusFor(string $status): string
    {
        return match ($status) {
            'Awaiting Parts' => 'Awaiting Parts',
            'Ready for Collection', 'Completed' => 'Ready for Collection',
            default => 'In Workshop',
        };
    }
}
