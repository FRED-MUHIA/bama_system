<?php

namespace Modules\Automotive\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Modules\Automotive\Models\JobCard;
use Modules\Automotive\Models\Part;
use Modules\Automotive\Models\PartRequest;
use Modules\Automotive\Models\PartRequestItem;

class AutomotiveInventoryService
{
    public function __construct(private AutomotiveNumberService $numbers) {}

    public function part(array $data): Part
    {
        $product = null;
        if (empty($data['product_id'])) {
            $product = Product::create([
                'name' => $data['name'],
                'sku' => $data['part_number'],
                'price' => $data['selling_price'] ?? 0,
                'cost_price' => $data['cost_price'] ?? 0,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'reorder_level' => $data['reorder_level'] ?? 0,
                'stock_unit' => 'pcs',
                'is_active' => true,
            ]);
            $data['product_id'] = $product->id;
        }

        return Part::create($data);
    }

    public function request(JobCard $job, array $data, array $items): PartRequest
    {
        return $this->numbers->transaction(function () use ($job, $data, $items) {
            $request = PartRequest::create([
                ...$data,
                'job_card_id' => $job->id,
                'request_number' => $data['request_number'] ?? $this->numbers->next('PR', PartRequest::class, 'request_number'),
            ]);

            foreach ($items as $item) {
                $part = ! empty($item['part_id']) ? Part::find($item['part_id']) : null;
                PartRequestItem::create([
                    ...$item,
                    'part_request_id' => $request->id,
                    'part_name' => $item['part_name'] ?? $part?->name ?? 'Part',
                    'product_id' => $item['product_id'] ?? $part?->product_id,
                    'unit_cost' => $item['unit_cost'] ?? $part?->cost_price ?? 0,
                    'unit_price' => $item['unit_price'] ?? $part?->selling_price ?? 0,
                    'approved_qty' => $item['approved_qty'] ?? $item['requested_qty'] ?? 0,
                ]);

                if ($part) {
                    $part->increment('reserved_quantity', (float) ($item['requested_qty'] ?? 0));
                }
            }

            return $request->fresh('items');
        });
    }

    public function issue(PartRequestItem $item, float $quantity): PartRequestItem
    {
        return $this->numbers->transaction(function () use ($item, $quantity) {
            $part = $item->part;
            if ($part && (float) $part->stock_quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Cannot issue more parts than are available in stock.']);
            }

            if ($part) {
                $part->decrement('stock_quantity', $quantity);
                $part->decrement('reserved_quantity', min((float) $part->reserved_quantity, $quantity));
            }

            if ($item->product) {
                $item->product->decrement('stock_quantity', $quantity);
            }

            $item->increment('issued_qty', $quantity);
            $item->update(['status' => 'Issued']);
            $item->request?->update(['status' => 'Issued']);

            return $item->fresh();
        });
    }

    public function return(PartRequestItem $item, float $quantity): PartRequestItem
    {
        return $this->numbers->transaction(function () use ($item, $quantity) {
            $part = $item->part;
            if ($part) {
                $part->increment('stock_quantity', $quantity);
            }
            if ($item->product) {
                $item->product->increment('stock_quantity', $quantity);
            }

            $item->increment('returned_qty', $quantity);
            $item->update(['status' => 'Returned']);

            return $item->fresh();
        });
    }

    public function compatibleParts(\Modules\Automotive\Models\Vehicle $vehicle)
    {
        return Part::query()
            ->where(function ($query) use ($vehicle) {
                $query->whereJsonContains('vehicle_compatibility->make', $vehicle->make)
                    ->orWhereHas('compatibilities', fn ($q) => $q->where('make', $vehicle->make)->where('model', $vehicle->model));
            })
            ->get();
    }
}
