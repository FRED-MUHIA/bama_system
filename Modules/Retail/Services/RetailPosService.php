<?php

namespace Modules\Retail\Services;

use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\PosOrder;
use App\Models\Product;
use App\Services\DocumentService;
use App\Services\FinanceService;
use App\Services\IamService;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Models\RetailCashDrawer;
use Modules\Retail\Models\RetailGiftCard;
use Modules\Retail\Models\RetailProductVariant;
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
        private FinanceService $finance,
    ) {
    }

    public function createSale(array $data): PosOrder
    {
        return DB::transaction(function () use ($data) {
            $data = app(RetailShopContext::class)->saleContext($data);
            if (! empty($data['retail_cash_drawer_id'])) {
                $drawer = RetailCashDrawer::where('business_id', \App\Support\ActiveBusiness::id())->where('branch_id', $data['branch_id'])->where('cashier_id', auth()->id())
                    ->where('status', 'Open')->find($data['retail_cash_drawer_id']);
                if (! $drawer) {
                    throw ValidationException::withMessages(['retail_cash_drawer_id' => 'Select your open drawer in this shop.']);
                }
            }
            $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
            $items = $this->prepareItems($data['items'] ?? [], $client, $data);
            $totals = $this->documents->totals($items);
            $payments = $this->validPayments($data['payments'] ?? []);
            $amountPaid = round($payments->sum('amount'), 2);
            if ($amountPaid - (float) $totals['total'] > 0.005) {
                throw ValidationException::withMessages(['payments' => 'Recorded payment cannot be greater than the sale total.']);
            }
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
                $order->items()->create($this->itemForTable('pos_order_items', $item + ['line_total' => $this->documents->lineTotal($item)]));
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
            $this->postSaleFinance($order);
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
        $data = app(RetailShopContext::class)->saleContext($data);
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
        abort_unless((int) $drawer->cashier_id === (int) auth()->id(), 403);
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

    public function void(PosOrder $order, string $reason, ?int $authorizedBy = null): PosOrder
    {
        if ($order->status === 'cancelled') {
            return $order->load('retailExtension');
        }

        return DB::transaction(function () use ($order, $reason, $authorizedBy) {
            $order->load('items', 'payments.paymentMethod', 'retailExtension.cashDrawer');
            $paymentColumns = Schema::hasTable('pos_order_payments') ? Schema::getColumnListing('pos_order_payments') : [];
            $giftCardPayments = $order->payments->filter(fn ($payment) => str_contains(strtolower((string) ((in_array('method_type', $paymentColumns, true) ? $payment->method_type : null) ?: $payment->paymentMethod?->name)), 'gift'));
            if ($giftCardPayments->isNotEmpty() && Schema::hasTable('retail_gift_card_transactions')) {
                $giftTransactions = \Modules\Retail\Models\RetailGiftCardTransaction::where('business_id', $order->business_id)
                    ->where('pos_order_id', $order->id)->where('type', 'Redeemed')->get();
                foreach ($giftTransactions as $transaction) {
                    $card = RetailGiftCard::whereKey($transaction->retail_gift_card_id)->lockForUpdate()->first();
                    if ($card) {
                        $card->increment('balance', (float) $transaction->amount);
                        $card->transactions()->create(['pos_order_id' => $order->id, 'type' => 'Reversed', 'amount' => $transaction->amount, 'balance_after' => $card->fresh()->balance, 'reference' => 'Sale cancellation']);
                    }
                }
            }
            $financeJournals = \App\Models\JournalEntry::where('business_id', $order->business_id)
                ->where(function ($query) use ($order) {
                    $query->where(function ($source) use ($order) {
                        $source->where('source_type', PosOrder::class)->where('source_id', $order->id);
                    })->orWhere(function ($source) use ($order) {
                        $source->where('source_type', \App\Models\PosOrderPayment::class)
                            ->whereIn('source_id', $order->payments->pluck('id'));
                    });
                })->where('status', 'Posted')->get();
            $this->stock->syncSaleItems($order->items, collect(), $order, 'Retail POS void '.$order->order_number);
            foreach ($financeJournals as $journal) {
                $this->finance->reverse($journal, 'POS sale cancelled: '.$reason);
            }

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

            $this->iam->audit('retail.pos.sale.voided', $order, [
                'authorized_by' => $authorizedBy,
                'reason' => $reason,
                'payment_refund_required' => (float) $order->amount_paid > 0,
            ]);

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
            [$product, $variant] = $this->resolveProductAndVariant($item);
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? $product?->price ?? 0);
            $manualDiscount = (float) ($item['discount'] ?? 0);
            $lineBase = $quantity * $unitPrice;
            $promotionDiscount = $product ? $this->promotions->discountFor($product, $lineBase, $client, $saleData['branch_id'] ?? null) : 0;
            $taxRate = $item['tax_rate'] ?? $product?->retailProfile?->tax_class ?? 0;

            return [
                'product_id' => $product?->id,
                'retail_product_variant_id' => $variant?->id,
                'title' => $item['title'] ?? $product?->name ?? $item['description'] ?? 'Quick sale item',
                'description' => $item['description'] ?? $product?->description ?? $product?->name ?? 'Quick sale item',
                'variant_description' => $variant?->displayName(),
                'sku_snapshot' => $variant?->sku ?: $product?->sku,
                'barcode_snapshot' => $variant?->barcode ?: $product?->barcode,
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

                $method = ! empty($payment['payment_method_id'])
                    ? PaymentMethod::where('business_id', \App\Support\ActiveBusiness::id())->find($payment['payment_method_id'])
                    : null;

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

    private function resolveProductAndVariant(array $item): array
    {
        $product = ! empty($item['product_id']) ? Product::with('retailProfile')->find($item['product_id']) : null;
        $variant = null;

        if (! empty($item['retail_product_variant_id'])) {
            $variant = RetailProductVariant::with('product.retailProfile', 'parentProduct.retailProfile')
                ->find($item['retail_product_variant_id']);

            if ($variant) {
                if ($product && ! in_array((int) $product->id, [(int) $variant->product_id, (int) $variant->parent_product_id], true)) {
                    throw ValidationException::withMessages([
                        'items' => 'One selected variant does not belong to its product.',
                    ]);
                }

                $product = $variant->product ?: $product;
            }
        }

        return [$product, $variant];
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

    private function recordPayments(PosOrder $order, Collection $payments): void
    {
        if (! Schema::hasTable('pos_order_payments')) return;
        $availableColumns = Schema::getColumnListing('pos_order_payments');

        foreach ($payments as $payment) {
            $record = [
                'business_id' => $order->business_id,
                'payment_method_id' => $payment['payment_method_id'],
                'method_type' => $payment['method_type'],
                'retail_gift_card_id' => $payment['retail_gift_card_id'],
                'amount' => $payment['amount'],
                'payment_date' => now()->toDateString(),
                'reference' => $payment['reference'],
                'notes' => trim(($payment['method_type'] ?? 'Retail payment').($payment['notes'] ? ': '.$payment['notes'] : '')),
            ];
            $order->payments()->create(collect($record)->only($availableColumns)->all());
        }
    }

    private function redeemGiftCards(PosOrder $order, Collection $payments): void
    {
        foreach ($payments->whereNotNull('retail_gift_card_id') as $payment) {
            $card = RetailGiftCard::where('business_id', \App\Support\ActiveBusiness::id())->find($payment['retail_gift_card_id']);
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

    private function postSaleFinance(PosOrder $order): void
    {
        if (! $this->finance->ready() || (float) $order->total <= 0) return;

        $salesAccount = $this->finance->account('4000');
        $receivableAccount = $this->finance->account('1200');
        $lines = [
            ['finance_account_id' => $receivableAccount->id, 'description' => 'Retail POS sale '.$order->order_number, 'debit' => (float) $order->total, 'credit' => 0],
            ['finance_account_id' => $salesAccount->id, 'description' => 'Retail POS sale '.$order->order_number, 'debit' => 0, 'credit' => max((float) $order->total - (float) $order->tax_total, 0)],
        ];
        if ((float) $order->tax_total > 0) {
            $lines[] = ['finance_account_id' => $this->finance->account('2200')->id, 'description' => 'Retail POS tax '.$order->order_number, 'debit' => 0, 'credit' => (float) $order->tax_total];
        }
        $this->finance->post([
            'business_id' => $order->business_id,
            'entry_date' => $order->order_date,
            'description' => 'Retail POS sale '.$order->order_number,
            'source_type' => PosOrder::class,
            'source_id' => $order->id,
        ], $lines);

        if (! Schema::hasTable('pos_order_payments')) return;
        $paymentColumns = Schema::getColumnListing('pos_order_payments');
        foreach ($order->payments()->with('paymentMethod')->get() as $payment) {
            if ((float) $payment->amount <= 0) continue;
            $method = strtolower((string) ((in_array('method_type', $paymentColumns, true) ? $payment->method_type : null) ?: $payment->paymentMethod?->name ?: 'cash'));
            if (str_contains($method, 'gift') || str_contains($method, 'store credit')) {
                continue;
            }
            $debitCode = str_contains($method, 'cash') ? '1000' : '1100';
            $this->finance->post([
                'business_id' => $order->business_id,
                'entry_date' => $payment->payment_date,
                'description' => 'Retail POS payment '.$order->order_number.' · '.((in_array('method_type', $paymentColumns, true) ? $payment->method_type : null) ?: 'Payment'),
                'source_type' => \App\Models\PosOrderPayment::class,
                'source_id' => $payment->id,
            ], [
                ['finance_account_id' => $this->finance->account($debitCode)->id, 'description' => 'POS payment received', 'debit' => (float) $payment->amount, 'credit' => 0],
                ['finance_account_id' => $receivableAccount->id, 'description' => 'POS receivable settled', 'debit' => 0, 'credit' => (float) $payment->amount],
            ]);
        }
    }

    private function statusFor(string $saleType, float $amountPaid, float $total): string
    {
        if ($saleType === 'Layaway') {
            return 'layaway';
        }

        return $amountPaid >= $total ? 'paid' : 'pending';
    }
}
