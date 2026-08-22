<?php

namespace Modules\Automotive\Services;

use Modules\Automotive\Models\Diagnostic;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\WorkshopBay;

class WorkshopService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function bay(array $data): WorkshopBay
    {
        return WorkshopBay::create($data);
    }

    public function diagnostic(JobCard $job, array $data): Diagnostic
    {
        $diagnostic = Diagnostic::create([
            ...$data,
            'job_card_id' => $job->id,
            'vehicle_id' => $job->vehicle_id,
            'diagnostic_number' => $data['diagnostic_number'] ?? $this->numbers->next('DIA', Diagnostic::class, 'diagnostic_number'),
            'diagnostic_date' => $data['diagnostic_date'] ?? today(),
        ]);

        if (! empty($data['diagnosis'])) {
            $job->update(['diagnosis' => $data['diagnosis'], 'status' => 'Diagnosis']);
        }

        return $diagnostic;
    }

    public function board(): array
    {
        $columns = ['Bookings', 'Checked In', 'Inspection', 'Diagnosis', 'Awaiting Approval', 'Awaiting Parts', 'Ready', 'In Progress', 'Quality Check', 'Ready for Collection', 'Completed'];

        return collect($columns)
            ->mapWithKeys(fn ($status) => [$status => JobCard::with('client', 'vehicle', 'technician', 'serviceAdvisor')->where('status', $status)->latest()->limit(30)->get()])
            ->all();
    }
}
