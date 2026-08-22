<?php

namespace Modules\PrintingBranding\Services;

use Illuminate\Support\Str;
use Modules\PrintingBranding\Models\JobTicket;
use Modules\PrintingBranding\Models\ProductionJob;

class JobTicketService
{
    public function __construct(private PrintingNumberService $numbers) {}

    public function createForJob(ProductionJob $job): JobTicket
    {
        return JobTicket::firstOrCreate(
            ['job_id' => $job->id],
            [
                'ticket_number' => $this->numbers->next('TKT', JobTicket::class, 'ticket_number'),
                'qr_token' => Str::uuid()->toString(),
                'barcode' => $job->job_number,
                'ticket_data' => $this->ticketData($job->loadMissing('client', 'machine')),
            ]
        );
    }

    public function ticketData(ProductionJob $job): array
    {
        return [
            'job_number' => $job->job_number,
            'client' => $job->client?->name,
            'product' => $job->product_name,
            'quantity' => (float) $job->quantity,
            'specifications' => $job->specifications ?? [],
            'artwork' => $job->artwork_path,
            'machine' => $job->machine?->name,
            'deadline' => $job->delivery_date?->toDateString(),
            'priority' => $job->priority,
            'production_notes' => $job->production_notes,
        ];
    }
}
