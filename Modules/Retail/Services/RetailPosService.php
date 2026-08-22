<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\PosOrder;
use App\Models\Product;
use App\Services\DocumentService;
use App\Services\IamService;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\RetailCashDrawer;
use Modules\Retail\Models\RetailGiftCard;
use Shared\Compliance\Etims\Contracts\EtimsComplianceServiceContract;

class RetailPosService
{
    public function __construct(
        private DocumentService $documents,
        private StockService $stock,
        private RetailPromotionService $promotions,
        private RetailLoyaltyService $loyalty,
        private RetailGiftCardService $giftCards,
        private IamService $iam,
        private EtimsComplianceServiceContract $etims,
    ) {
    }

    public function createSale(array $data): PosOrder
    {
        return DB::transaction(function () use ($data) {
            $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
            $items = $this->prepareItems($data['items'] ?? [], $client, $data);
            $totals = $this->documents->totals($items);
            $payments = $this->validPayments($data['payments'] ?? []);
            $amountPaid = round($payments->sum('amount'), 2);
            $primaryPaymentMethodId = $payments->first()['payment_method_id'] ?? null;

            $order = PosOrder::create([
                'client_id' => $client?->id,
                'payment_method_id' => $primaryPaymentMethodId,
                'order_number' => $this->documents->number('pos_order'),
                'tracking_key' => str()->uuid()->toString(),
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'customer_name' => $client?->name ?: ($data['customer_name'] ?? 'Walk-in customer'),
                'customer_phone' => $client?->phone ?: ($data['customer_phone'] ?? null),
                'customer_email' => $client?->email ?: ($data['customer_email'] ?? null),
                'customer_type' => $data['customer_type'] ?? 'Retail Customer',
                'status' => $this->statusFor($data['sale_type'] ?? 'Sale', $amountPaid, (float) $totals['total']),
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discountTotal'],
                'tax_total' => $totals['taxTotal'],
                'custom_amount' => 0,
                'total' => $totals['total'],
                'amount_paid' => $amountPaid,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create($item + ['line_total' => $this->documents->lineTotal($item)]);
            }

            $this->stock->syncSaleItems(collect(), collect($items), $order, 'Retail POS '.$order->order_number);
            $this->recordPayments($order, $payments);

            $order->retailExtension()->create([
                'branch_id' => $data['branch_id'] ?? null,
                'cashier_id' => $data['cashier_id'] ?? auth()->id(),
                'retail_cash_drawer_id' => $data['retail_cash_drawer_id'] ?? null,
                'retail_promotion_id' => $data['retail_promotion_id'] ?? null,
                'sale_type' => $data['sale_type'] ?? 'Sale',
                'channel' => $data['channel'] ?? 'Store',
                'coupon_code' => $data['coupon_code'] ?? null,
                'layaway_due_at' => $data['layaway_due_at'] ?? null,
                'split_payment_summary' => $payments->values()->all(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->redeemGiftCards($order, $payments);
            $this->applyLoyalty($order, $client);
            $this->updateCashDrawer($data['retail_cash_drawer_id'] ?? null, $payments);
            $this->etims->submitSale($order->load('items.product', 'payments.paymentMethod'), [
                'industry' => 'retail',
                'channel' => $data['channel'] ?? 'Store',
                'offline' => (bool) ($data['offline_mode'] ?? false),
            ]);
            $this->iam->audit('retail.pos.sale.created', $order);

            return $order->load('items.product', 'payments.paymentMethod', 'retailExtension', 'etimsSubmissions');
        });
    }

    public function openDrawer(array $data): RetailCashDrawer
    {
        $drawer = RetailCashDrawer::create([
            'branch_id' => $data['branch_id'] ?? null,
            'cashier_id' => $data['cashier_id'] ?? auth()->id(),
            'drawer_number' => $data['drawer_number'],
            'opened_at' => now(),
            'opening_float' => $data['opening_float'] ?? 0,
            'expected_cash' => $data['opening_float'] ?? 0,
            'status' => 'Open',
        ]);

        $this->iam->audit('retail.pos.drawer.opened', $drawer);

        return $drawer;
    }

    public function closeDrawer(RetailCashDrawer $drawer, array $data): RetailCashDrawer
    {
        $counted = (float) ($data['counted_cash'] ?? 0);
        $expected = (float) $drawer->opening_float + (float) $drawer->cash_sales - (float) $drawer->cash_refunds;

        $drawer->update([
            'closed_at' => now(),
            'expected_cash' => round($expected, 2),
            'counted_cash' => round($counted, 2),
            'variance' => round($counted - $expected, 2),
            'status' => 'Closed',
        ]);

        $this->iam->audit('retail.pos.drawer.closed', $drawer);

        return $drawer->refresh();
    }

    public function void(PosOrder $order, ?string $reason = null): PosOrder
    {
        if ($order->status === 'cancelled') {
            return $order->load('retailExtension');
        }

        return DB::transaction(function () use ($order, $reason) {
            $order->load('items', 'payments.paymentMethod', 'retailExtension.cashDrawer');
            $this->stock->syncSaleItems($order->items, collect(), $order, 'Retail POS void '.$order->order_number);

            $order->update([
                'status' => 'cancelled',
                'notes' => trim(($order->notes ? $order->notes.PHP_EOL : '').'Voided: '.($reason ?: 'No reason supplied')),
            ]);

            $extension = $order->retailExtension;
            if ($extension) {
                $extension->update(['voided_at' => now()]);
                $cashDrawer = $extension->cashDrawer;
                if ($cashDrawer) {
                    $cashRefunds = $this->cashPayments($order->payments);
                    $expected = (float) $cashDrawer->opening_float + (float) $cashDrawer->cash_sales - ((float) $cashDrawer->cash_refunds + $cashRefunds);
                    $cashDrawer->increment('cash_refunds', $cashRefunds);
                    $cashDrawer->update([
                        'expected_cash' => round($expected, 2),
                    ]);
                }
            }

            $this->iam->audit('retail.pos.sale.voided', $order);

            return $order->refresh()->load('retailExtension', 'items', 'payments');
        });
    }

    private function prepareItems(array $items, ?Client $client, array $saleData): array
    {
        $items = array_values(array_filter($items, fn ($item) => filled($item['product_id'] ?? null) || filled($item['description'] ?? null)));

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Add at least one product to the POS cart.']);
        }

        return $this->documents->normalizeItems(array_map(function (array $item) use ($client, $saleData) {
            $product = ! empty($item['product_id']) ? Product::with('retailProfile')->find($item['product_id']) : null;
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? $product?->price ?? 0);
            $manualDiscount = (float) ($item['discount'] ?? 0);
            $lineBase = $quantity * $unitPrice;
            $promotionDiscount = $product ? $this->promotions->discountFor($product, $lineBase, $client, $saleData['branch_id'] ?? null) : 0;
            $taxRate = $item['tax_rate'] ?? $product?->retailProfile?->tax_class ?? 0;

            return [
                'product_id' => $product?->id,
                'title' => $item['title'] ?? $product?->name ?? $item['description'] ?? 'Quick sale item',
                'description' => $item['description'] ?? $product?->description ?? $product?->name ?? 'Quick sale item',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => max($manualDiscount, $promotionDiscount),
                'tax_rate' => is_numeric($taxRate) ? (float) $taxRate : 0,
            ];
        }, $items));
    }

    private function validPayments(array $payments): Collection
    {
        return collect($payments)
            ->map(function (array $payment) {
                $amount = (float) ($payment['amount'] ?? 0);
                if ($amount <= 0) {
                    return null;
                }

                $method = ! empty($payment['payment_method_id']) ? PaymentMethod::find($payment['payment_method_id']) : null;

                return [
                    'payment_method_id' => $method?->id,
                    'method_type' => $payment['method_type'] ?? $method?->type ?? $method?->name ?? 'Manual',
                    'amount' => round($amount, 2),
                    'reference' => $payment['reference'] ?? null,
                    'retail_gift_card_id' => $payment['retail_gift_card_id'] ?? null,
                    'notes' => $payment['notes'] ?? null,
                ];
            })
            ->filter()
            ->values();
    }

    private function recordPayments(PosOrder $order, Collection $payments): void
    {
        foreach ($payments as $payment) {
            $order->payments()->create([
                'payment_method_id' => $payment['payment_method_id'],
                'amount' => $payment['amount'],
                'payment_date' => now()->toDateString(),
                'reference' => $payment['reference'],
                'notes' => trim(($payment['method_type'] ?? 'Retail payment').($payment['notes'] ? ': '.$payment['notes'] : '')),
            ]);
        }
    }

    private function redeemGiftCards(PosOrder $order, Collection $payments): void
    {
        foreach ($payments->whereNotNull('retail_gift_card_id') as $payment) {
            $card = RetailGiftCard::find($payment['retail_gift_card_id']);
            if ($card) {
                $this->giftCards->redeem($card, (float) $payment['amount'], $order, $payment['reference']);
            }
        }
    }

    private function applyLoyalty(PosOrder $order, ?Client $client): void
    {
        if ($client && (float) $order->amount_paid > 0 && in_array($order->status, ['paid', 'pending'], true)) {
            $this->loyalty->earn($client, (float) $order->amount_paid, $order, 'Retail POS sale');
        }
    }

    private function updateCashDrawer(?int $drawerId, Collection $payments): void
    {
        if (! $drawerId) {
            return;
        }

        $drawer = RetailCashDrawer::find($drawerId);
        if (! $drawer) {
            return;
        }

        $cashSales = $this->cashPayments($payments);
        if ($cashSales <= 0) {
            return;
        }

        $expected = (float) $drawer->opening_float + (float) $drawer->cash_sales + $cashSales - (float) $drawer->cash_refunds;
        $drawer->increment('cash_sales', $cashSales);
        $drawer->update([
            'expected_cash' => round($expected, 2),
        ]);
    }

    private function cashPayments(Collection $payments): float
    {
        return round($payments->filter(function ($payment) {
            $method = strtolower((string) data_get($payment, 'method_type', data_get($payment, 'paymentMethod.name', data_get($payment, 'notes'))));

            return str_contains($method, 'cash');
        })->sum('amount'), 2);
    }

    private function statusFor(string $saleType, float $amountPaid, float $total): string
    {
        if ($saleType === 'Layaway') {
            return 'layaway';
        }

        return $amountPaid >= $total ? 'paid' : 'pending';
    }
}
