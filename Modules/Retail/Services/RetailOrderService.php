<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\PosOrder;
use App\Models\Product;
use App\Services\DocumentService;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Retail\Models\RetailDelivery;
use Modules\Retail\Models\RetailOrder;
use Modules\Retail\Models\RetailProductVariant;

class RetailOrderService
{
    public function __construct(
        private RetailNumberService $numbers,
        private DocumentService $documents,
        private StockService $stock,
    ) {
    }

    public function create(array $data): RetailOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $this->prepareItems($data['items']);
            $totals = $this->documents->totals($items->all());
            $posOrder = $this->createPosOrder($data, $items, $totals);

            $order = RetailOrder::create([
                'client_id' => $data['client_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'pos_order_id' => $posOrder->id,
                'order_number' => $posOrder->order_number ?: ($data['order_number'] ?? $this->numbers->orderNumber()),
                'channel' => $data['channel'],
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'Draft',
                'requested_delivery_at' => $data['requested_delivery_at'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discountTotal'],
                'tax_total' => $totals['taxTotal'],
                'total' => $totals['total'],
                'metadata' => array_merge((array) ($data['metadata'] ?? []), [
                    'source' => 'retail_order_form',
                    'pos_order_id' => $posOrder->id,
                    'pos_order_number' => $posOrder->order_number,
                ]),
            ]);

            foreach ($items as $item) {
                $order->items()->create($this->itemForTable('retail_order_items', $item));
            }

            return $order->load('items.product', 'client', 'branch', 'posOrder.items.product', 'posOrder.retailExtension');
        });
    }

    public function scheduleDelivery(RetailOrder $order, array $data): RetailDelivery
    {
        return $order->delivery()->updateOrCreate(
            ['retail_order_id' => $order->id],
            $data + ['status' => $data['status'] ?? 'Scheduled']
        );
    }

    private function prepareItems(array $items): Collection
    {
        return collect($this->documents->normalizeItems($items))
            ->map(function (array $item) {
                [$product, $variant] = $this->resolveProductAndVariant($item);
                $title = $item['title'] ?? $product?->name ?? $item['description'] ?? 'Manual retail order item';

                return [
                    'product_id' => $product?->id,
                    'retail_product_variant_id' => $variant?->id,
                    'title' => $title,
                    'description' => $item['description'] ?? $product?->description ?? $title,
                    'variant_description' => $variant?->displayName(),
                    'sku_snapshot' => $variant?->sku ?: $product?->sku,
                    'barcode_snapshot' => $variant?->barcode ?: $product?->barcode,
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? $product?->price ?? 0),
                    'discount' => (float) ($item['discount'] ?? 0),
                    'tax_rate' => (float) ($item['tax_rate'] ?? $product?->retailProfile?->tax_class ?? 0),
                ];
            })
            ->map(fn (array $item) => $item + ['line_total' => $this->documents->lineTotal($item)])
            ->values();
    }

    private function createPosOrder(array $data, Collection $items, array $totals): PosOrder
    {
        $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        $status = $this->posStatusFor($data['status'] ?? 'Draft');

        $order = PosOrder::create([
            'client_id' => $client?->id,
            'order_number' => $data['order_number'] ?? $this->documents->number('pos_order'),
            'tracking_key' => str()->uuid()->toString(),
            'order_date' => $data['order_date'] ?? now()->toDateString(),
            'customer_name' => $client?->name,
            'customer_phone' => $client?->phone,
            'customer_email' => $client?->email,
            'customer_type' => 'Retail Customer',
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discountTotal'],
            'tax_total' => $totals['taxTotal'],
            'custom_amount' => 0,
            'total' => $totals['total'],
            'amount_paid' => 0,
            'notes' => $this->posNotesFor($data),
        ]);

        foreach ($items as $item) {
            $order->items()->create($this->itemForTable('pos_order_items', $item));
        }

        $this->stock->syncSaleItems(
            collect(),
            $status === 'cancelled' ? collect() : $items,
            $order,
            'Retail POS '.$order->order_number
        );

        if (Schema::hasTable('retail_sales_extensions')) {
            $order->retailExtension()->create([
                'branch_id' => $data['branch_id'] ?? null,
                'cashier_id' => auth()->id(),
                'sale_type' => 'Sale',
                'channel' => $this->posChannelFor($data['channel'] ?? 'Store'),
                'split_payment_summary' => [],
                'notes' => $this->posNotesFor($data),
            ]);
        }

        return $order;
    }

    private function itemForTable(string $table, array $item): array
    {
        if (! Schema::hasTable($table)) {
            return $item;
        }

        return collect($item)
            ->only(Schema::getColumnListing($table))
            ->all();
    }

    private function resolveProductAndVariant(array $item): array
    {
        $product = ! empty($item['product_id']) ? Product::with('retailProfile')->find($item['product_id']) : null;
        $variant = null;

        if (! empty($item['retail_product_variant_id'])) {
            $variant = RetailProductVariant::with('product.retailProfile', 'parentProduct.retailProfile')
                ->find($item['retail_product_variant_id']);

            if ($variant) {
                $product = $variant->product ?: $product;
            }
        }

        return [$product, $variant];
    }

    private function posStatusFor(string $status): string
    {
        return match ($status) {
            'Confirmed', 'Packed', 'Shipped', 'Delivered' => 'approved',
            'Cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    private function posChannelFor(string $channel): string
    {
        return match ($channel) {
            'Mobile Commerce' => 'Mobile POS',
            'Online Store', 'Marketplace' => 'Online Store',
            default => 'Store',
        };
    }

    private function posNotesFor(array $data): string
    {
        return trim('Retail order'.PHP_EOL.'Channel: '.($data['channel'] ?? 'Store').PHP_EOL.'Status: '.($data['status'] ?? 'Draft'));
    }
}
