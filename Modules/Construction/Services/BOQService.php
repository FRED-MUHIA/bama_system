<?php

namespace Modules\Construction\Services;

use Modules\Construction\Models\ConstructionBoq;
use Modules\Construction\Models\ConstructionBoqItem;
use Modules\Construction\Models\ConstructionBoqSection;
use Modules\Construction\Models\ConstructionRateComponent;

class BOQService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function create(array $data): ConstructionBoq
    {
        return ConstructionBoq::create([
            ...$data,
            'boq_number' => $data['boq_number'] ?? $this->numbers->next('BOQ', ConstructionBoq::class, 'boq_number'),
        ]);
    }

    public function addSection(ConstructionBoq $boq, array $data): ConstructionBoqSection
    {
        return ConstructionBoqSection::create([
            ...$data,
            'boq_id' => $boq->id,
        ]);
    }

    public function addItem(ConstructionBoq $boq, array $data): ConstructionBoqItem
    {
        $unitRate = $data['unit_rate'] ?? (
            (float) ($data['material_rate'] ?? 0)
            + (float) ($data['labour_rate'] ?? 0)
            + (float) ($data['equipment_rate'] ?? 0)
            + (float) ($data['subcontract_rate'] ?? 0)
        );
        $quantity = (float) ($data['quantity'] ?? 0);

        $item = ConstructionBoqItem::create([
            ...$data,
            'boq_id' => $boq->id,
            'unit_rate' => $unitRate,
            'total_amount' => round($quantity * $unitRate, 2),
        ]);

        $this->recalculate($boq);

        return $item;
    }

    public function addRateComponent(ConstructionBoqItem $item, array $data): ConstructionRateComponent
    {
        $component = ConstructionRateComponent::create([
            ...$data,
            'boq_item_id' => $item->id,
            'amount' => round((float) ($data['quantity'] ?? 0) * (float) ($data['rate'] ?? 0), 2),
        ]);

        $this->refreshItemFromRateAnalysis($item->fresh());

        return $component;
    }

    public function recalculate(ConstructionBoq $boq): ConstructionBoq
    {
        $subtotal = (float) $boq->items()->sum('total_amount');
        $grand = $subtotal + (float) $boq->preliminaries + (float) $boq->contingency + (float) $boq->tax;
        $boq->update(['subtotal' => $subtotal, 'grand_total' => $grand]);

        foreach ($boq->sections as $section) {
            $section->update(['total_amount' => (float) $section->items()->sum('total_amount')]);
        }

        return $boq->fresh();
    }

    private function refreshItemFromRateAnalysis(ConstructionBoqItem $item): void
    {
        $components = $item->rateComponents;
        if ($components->isEmpty()) {
            return;
        }

        $direct = (float) $components->sum('amount');
        $item->update([
            'unit_rate' => $direct,
            'total_amount' => round((float) $item->quantity * $direct, 2),
        ]);

        $this->recalculate($item->boq);
    }
}
