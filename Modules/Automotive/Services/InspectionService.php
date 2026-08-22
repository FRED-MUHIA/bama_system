<?php

namespace Modules\Automotive\Services;

use Modules\Automotive\Models\Estimate;
use Modules\Automotive\Models\Inspection;
use Modules\Automotive\Models\InspectionItem;

class InspectionService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function create(array $data, array $items = []): Inspection
    {
        return $this->numbers->transaction(function () use ($data, $items) {
            $inspection = Inspection::create([
                ...$data,
                'inspection_number' => $data['inspection_number'] ?? $this->numbers->next('INS', Inspection::class, 'inspection_number'),
                'inspection_date' => $data['inspection_date'] ?? today(),
            ]);

            foreach ($items ?: $this->defaultItems() as $item) {
                $this->addItem($inspection, $item);
            }

            return $inspection->fresh('items');
        });
    }

    public function addItem(Inspection $inspection, array $data): InspectionItem
    {
        return InspectionItem::create([
            ...$data,
            'inspection_id' => $inspection->id,
        ]);
    }

    public function recommendationsToEstimate(Inspection $inspection, AutomotiveEstimateService $estimates): Estimate
    {
        $items = $inspection->items()
            ->whereIn('result', ['Service Soon', 'Critical'])
            ->get()
            ->map(fn (InspectionItem $item) => [
                'type' => 'Labour',
                'category' => $item->result === 'Critical' ? 'Required Repairs' : 'Recommended Repairs',
                'description' => $item->section.' - '.$item->item,
                'quantity' => 1,
                'unit_price' => $item->estimated_cost,
                'unit_cost' => 0,
            ])
            ->all();

        return $estimates->create([
            'job_card_id' => $inspection->job_card_id,
            'vehicle_id' => $inspection->vehicle_id,
            'client_id' => $inspection->vehicle?->client_id,
            'status' => 'Draft',
        ], $items);
    }

    private function defaultItems(): array
    {
        return collect(['Engine', 'Transmission', 'Brakes', 'Suspension', 'Steering', 'Tyres', 'Battery', 'Electrical', 'Lights', 'Fluids', 'Cooling', 'Exhaust', 'Air Conditioning', 'Body', 'Interior', 'Safety Equipment'])
            ->map(fn ($section) => ['section' => $section, 'item' => $section.' check', 'result' => 'Not Checked'])
            ->all();
    }
}
