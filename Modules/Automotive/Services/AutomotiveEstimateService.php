<?php

namespace Modules\Automotive\Services;

use App\Models\Quotation;
use Modules\Automotive\Models\Estimate;
use Modules\Automotive\Models\EstimateItem;

class AutomotiveEstimateService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function create(array $data, array $items = []): Estimate
    {
        return $this->numbers->transaction(function () use ($data, $items) {
            $estimate = Estimate::create([
                ...$data,
                'estimate_number' => $data['estimate_number'] ?? $this->numbers->next('EST', Estimate::class, 'estimate_number'),
            ]);

            foreach ($items as $item) {
                $this->addItem($estimate, $item);
            }

            return $this->recalculate($estimate);
        });
    }

    public function addItem(Estimate $estimate, array $data): EstimateItem
    {
        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $taxRate = (float) ($data['tax_rate'] ?? 0);

        $item = EstimateItem::create([
            ...$data,
            'estimate_id' => $estimate->id,
            'line_total' => round(($quantity * $unitPrice) * (1 + ($taxRate / 100)), 2),
        ]);

        $this->recalculate($estimate);

        return $item;
    }

    public function approve(Estimate $estimate, ?array $itemIds = null): Estimate
    {
        return $this->numbers->transaction(function () use ($estimate, $itemIds) {
            $items = $estimate->items();
            if ($itemIds) {
                $items->whereIn('id', $itemIds)->update(['approval_status' => 'Approved']);
                $estimate->items()->whereNotIn('id', $itemIds)->update(['approval_status' => 'Rejected']);
                $estimate->update(['status' => 'Partially Approved', 'approved_at' => now()]);
            } else {
                $estimate->items()->update(['approval_status' => 'Approved']);
                $estimate->update(['status' => 'Approved', 'approved_at' => now()]);
            }

            $estimate->jobCard?->update(['status' => 'Approved']);

            return $estimate->fresh('items');
        });
    }

    public function toQuotation(Estimate $estimate): Quotation
    {
        $quotation = Quotation::create([
            'client_id' => $estimate->client_id,
            'quotation_number' => $estimate->estimate_number,
            'quotation_date' => today(),
            'valid_until' => today()->addDays(14),
            'status' => 'draft',
            'subtotal' => $estimate->total,
            'tax_total' => $estimate->tax_total,
            'total' => $estimate->total,
            'notes' => 'Generated from automotive estimate '.$estimate->estimate_number,
        ]);

        $estimate->update(['quotation_id' => $quotation->id]);
        $estimate->jobCard?->update(['quotation_id' => $quotation->id]);

        return $quotation;
    }

    public function recalculate(Estimate $estimate): Estimate
    {
        $items = $estimate->items()->get();
        $labour = (float) $items->where('type', 'Labour')->sum('line_total');
        $parts = (float) $items->where('type', 'Part')->sum('line_total');
        $consumables = (float) $items->where('type', 'Consumable')->sum('line_total');
        $external = (float) $items->where('type', 'External Service')->sum('line_total');
        $tax = (float) $items->sum(fn (EstimateItem $item) => ((float) $item->quantity * (float) $item->unit_price) * ((float) $item->tax_rate / 100));
        $total = $labour + $parts + $consumables + $external - (float) $estimate->discount_total;

        $estimate->update([
            'labour_total' => $labour,
            'parts_total' => $parts,
            'consumables_total' => $consumables,
            'external_total' => $external,
            'tax_total' => round($tax, 2),
            'total' => round($total, 2),
        ]);

        return $estimate->fresh('items');
    }
}
