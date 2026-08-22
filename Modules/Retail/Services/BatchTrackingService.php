<?php

namespace Modules\Retail\Services;

use App\Models\Product;
use App\Services\IamService;
use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\ProductBatch;
use Modules\Retail\Models\ScanEvent;

class BatchTrackingService
{
    public function createOrUpdate(Product $product, array $data): ProductBatch
    {
        $batch = ProductBatch::updateOrCreate(
            ['product_id' => $product->id, 'batch_number' => $data['batch_number']],
            [
                'supplier_id' => $data['supplier_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'retail_warehouse_id' => $data['retail_warehouse_id'] ?? null,
                'manufacture_date' => $data['manufacture_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'quantity' => $data['quantity'] ?? 0,
                'status' => $data['status'] ?? 'Active',
                'compliance_status' => $data['compliance_status'] ?? 'Compliant',
                'metadata' => $data['metadata'] ?? null,
            ]
        );

        app(IamService::class)->audit('retail.scanning.batch.saved', $batch);

        return $batch;
    }

    public function sell(ProductBatch $batch, float $quantity, ?ScanEvent $event = null): ProductBatch
    {
        return DB::transaction(function () use ($batch, $quantity, $event) {
            $batch = ProductBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $remaining = max((float) $batch->quantity - $quantity, 0);
            $batch->update([
                'quantity' => $remaining,
                'sold_quantity' => (float) $batch->sold_quantity + $quantity,
            ]);
            $batch->movements()->create([
                'scan_event_id' => $event?->id,
                'type' => 'Sale',
                'quantity' => -abs($quantity),
                'balance_after' => $remaining,
                'reference' => $event ? 'Scan '.$event->id : null,
            ]);

            return $batch->refresh();
        });
    }

    public function recall(ProductBatch $batch, string $reason): ProductBatch
    {
        $batch->update([
            'recall_status' => 'Recalled',
            'status' => 'Quarantined',
            'recall_reason' => $reason,
            'recalled_at' => now(),
        ]);
        app(IamService::class)->audit('retail.scanning.batch.recalled', $batch);

        return $batch->refresh();
    }
}
