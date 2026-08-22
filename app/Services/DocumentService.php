<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function number(string $type): string
    {
        $prefix = match ($type) {
            'quotation' => 'QT',
            'invoice' => 'INV',
            'receipt' => 'RCT',
            'pos_order' => 'POS',
            default => 'DOC',
        };

        $model = match ($type) {
            'quotation' => Quotation::class,
            'invoice' => Invoice::class,
            'receipt' => Receipt::class,
            'pos_order' => PosOrder::class,
        };

        $column = $type === 'pos_order' ? 'order_number' : "{$type}_number";
        $year = now()->format('Y');
        $base = "{$prefix}-{$year}-";

        $this->lockActiveBusiness();

        $highest = $model::query()
            ->where($column, 'like', $base.'%')
            ->lockForUpdate()
            ->pluck($column)
            ->reduce(function (int $highest, string $number) use ($base) {
                if (! preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $number, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        $next = $highest + 1;

        return sprintf('%s%s', $base, str_pad((string) $next, max(4, strlen((string) $next)), '0', STR_PAD_LEFT));
    }

    private function lockActiveBusiness(): void
    {
        $businessId = ActiveBusiness::id();

        if (! $businessId) {
            return;
        }

        DB::table('businesses')->where('id', $businessId)->lockForUpdate()->first();
    }

    public function totals(array $items): array
    {
        $subtotal = $discountTotal = $taxTotal = $total = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $unit = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);
            $base = $qty * $unit;
            $taxable = max($base - $discount, 0);
            $tax = $taxable * ($taxRate / 100);

            $subtotal += $base;
            $discountTotal += $discount;
            $taxTotal += $tax;
            $total += $taxable + $tax;
        }

        return compact('subtotal', 'discountTotal', 'taxTotal', 'total');
    }

    public function normalizeItems(array $items): array
    {
        return array_map(function (array $item) {
            $item['discount'] = $item['discount'] ?? 0;
            $item['tax_rate'] = $item['tax_rate'] ?? 0;

            if ($item['discount'] === '') {
                $item['discount'] = 0;
            }

            if ($item['tax_rate'] === '') {
                $item['tax_rate'] = 0;
            }

            return $item;
        }, $items);
    }

    public function lineTotal(array $item): float
    {
        $base = ((float) $item['quantity']) * ((float) $item['unit_price']);
        $taxable = max($base - ((float) ($item['discount'] ?? 0)), 0);

        return $taxable + ($taxable * (((float) ($item['tax_rate'] ?? 0)) / 100));
    }
}
