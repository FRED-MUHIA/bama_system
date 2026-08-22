<?php

namespace Modules\Retail\Services;

use App\Models\PosOrder;
use App\Services\IamService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\RetailReturnAuthorization;

class RetailReturnService
{
    public function __construct(private RetailNumberService $numbers, private StockService $stock)
    {
    }

    public function authorize(PosOrder $order, array $data): RetailReturnAuthorization
    {
        return DB::transaction(function () use ($order, $data) {
            $items = collect($data['items'] ?? [])->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0);
            $refundTotal = $items->sum(fn ($item) => (float) ($item['refund_amount'] ?? 0));

            $return = RetailReturnAuthorization::create([
                'pos_order_id' => $order->id,
                'client_id' => $order->client_id,
                'return_number' => $this->numbers->returnNumber(),
                'return_type' => $data['return_type'] ?? 'Return',
                'reason' => $data['reason'] ?? 'Customer return',
                'status' => $data['status'] ?? 'Pending',
                'approval_status' => $data['approval_status'] ?? 'Pending',
                'requested_at' => now(),
                'refund_method' => $data['refund_method'] ?? 'Original Payment',
                'refund_total' => $refundTotal,
            ]);

            foreach ($items as $item) {
                $return->items()->create($item);
            }

            app(IamService::class)->audit('retail.return.authorized', $return);

            return $return->load('items.product', 'order', 'client');
        });
    }

    public function approve(RetailReturnAuthorization $return): RetailReturnAuthorization
    {
        return DB::transaction(function () use ($return) {
            $return->load('items.product');
            foreach ($return->items as $item) {
                if ($item->product && $item->condition !== 'Damaged') {
                    $this->stock->receive($item->product, (float) $item->quantity, $return, 'Return '.$return->return_number, $return->reason);
                }
            }

            $return->update(['status' => 'Approved', 'approval_status' => 'Approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
            app(IamService::class)->audit('retail.return.approved', $return);

            return $return->refresh();
        });
    }
}
