<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\DeliveryNote;
use Modules\PrintingBranding\Models\Dispatch;
use Modules\PrintingBranding\Models\ProductionJob;

class DispatchService
{
    public function __construct(private PrintingNumberService $numbers) {}

    public function dispatch(ProductionJob $job, array $data): Dispatch
    {
        $dispatch = Dispatch::create($data + [
            'job_id' => $job->id,
            'client_id' => $job->client_id,
            'dispatch_number' => $data['dispatch_number'] ?? $this->numbers->next('DSP', Dispatch::class, 'dispatch_number'),
        ]);

        $job->update(['status' => in_array($dispatch->status, ['Delivered', 'Collected'], true) ? 'Completed' : 'Dispatched']);

        return $dispatch;
    }

    public function deliveryNote(Dispatch $dispatch): DeliveryNote
    {
        $job = $dispatch->job;

        return DeliveryNote::create([
            'dispatch_id' => $dispatch->id,
            'job_id' => $job->id,
            'client_id' => $dispatch->client_id,
            'delivery_note_number' => $this->numbers->next('DN', DeliveryNote::class, 'delivery_note_number'),
            'products' => [['product' => $job->product_name, 'quantity' => (float) $job->quantity]],
            'quantity' => $job->quantity,
            'delivery_address' => $dispatch->delivery_address,
            'driver' => $dispatch->driver?->name,
            'receiver' => $dispatch->receiver_name,
            'delivery_date' => $dispatch->delivery_date,
        ]);
    }
}
