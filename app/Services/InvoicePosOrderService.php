<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PosOrder;
use Illuminate\Support\Str;

class InvoicePosOrderService
{
    public function __construct(private DocumentService $documents) {}

    public function sync(Invoice $invoice): ?PosOrder
    {
        if ($invoice->isPartPayment()) {
            $invoice->posOrder()->delete();
            return null;
        }

        $invoice->load('client', 'items');

        $order = PosOrder::firstOrNew(['invoice_id' => $invoice->id]);
        $order->fill([
            'client_id' => $invoice->client_id,
            'order_number' => $order->order_number ?: $this->documents->number('pos_order'),
            'tracking_key' => $order->tracking_key ?: Str::upper(Str::random(12)),
            'order_date' => $invoice->invoice_date,
            'customer_name' => $invoice->client?->name,
            'customer_phone' => $invoice->client?->phone,
            'customer_email' => $invoice->client?->email,
            'customer_address' => $invoice->client?->address,
            'status' => $order->exists && $order->status === 'approved' ? 'approved' : 'pending',
            'subtotal' => $invoice->subtotal,
            'discount_total' => $invoice->discount_total,
            'tax_total' => $invoice->tax_total,
            'custom_amount' => 0,
            'total' => $invoice->total,
            'amount_paid' => $invoice->amount_paid ?? 0,
            'notes' => 'Generated from invoice '.$invoice->invoice_number,
        ])->save();

        if ($order->status !== 'approved') {
            $order->items()->delete();
            foreach ($invoice->items as $item) {
                $order->items()->create([
                    'title' => $item->title,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax_rate' => $item->tax_rate,
                    'line_total' => $item->line_total,
                ]);
            }
        }

        return $order;
    }
}
