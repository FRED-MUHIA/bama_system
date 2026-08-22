<?php

namespace Modules\Retail\Services;

use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\RetailDelivery;
use Modules\Retail\Models\RetailOrder;

class RetailOrderService
{
    public function __construct(private RetailNumberService $numbers, private DocumentService $documents)
    {
    }

    public function create(array $data): RetailOrder
    {
        return DB::transaction(function () use ($data) {
            $items = collect($data['items'])->map(function (array $item) {
                $item['description'] = $item['title'];
                $item['discount'] = $item['discount'] ?? 0;
                $item['tax_rate'] = $item['tax_rate'] ?? 0;
                $item['line_total'] = $this->documents->lineTotal($item);

                return $item;
            });

            $totals = $this->documents->totals($items->all());
            $order = RetailOrder::create([
                'client_id' => $data['client_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'order_number' => $data['order_number'] ?? $this->numbers->orderNumber(),
                'channel' => $data['channel'],
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'Draft',
                'requested_delivery_at' => $data['requested_delivery_at'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discountTotal'],
                'tax_total' => $totals['taxTotal'],
                'total' => $totals['total'],
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            return $order->load('items.product', 'client', 'branch');
        });
    }

    public function scheduleDelivery(RetailOrder $order, array $data): RetailDelivery
    {
        return $order->delivery()->updateOrCreate(
            ['retail_order_id' => $order->id],
            $data + ['status' => $data['status'] ?? 'Scheduled']
        );
    }
}
