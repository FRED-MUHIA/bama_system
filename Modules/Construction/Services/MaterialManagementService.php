<?php

namespace Modules\Construction\Services;

use Illuminate\Validation\ValidationException;
use Modules\Construction\Models\ConstructionMaterial;
use Modules\Construction\Models\ConstructionMaterialConsumption;
use Modules\Construction\Models\ConstructionMaterialRequest;

class MaterialManagementService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function material(array $data): ConstructionMaterial
    {
        return ConstructionMaterial::create([
            ...$data,
            'material_code' => $data['material_code'] ?? $this->numbers->next('MAT', ConstructionMaterial::class, 'material_code'),
        ]);
    }

    public function request(array $data): ConstructionMaterialRequest
    {
        return ConstructionMaterialRequest::create([
            ...$data,
            'request_number' => $data['request_number'] ?? $this->numbers->next('MR', ConstructionMaterialRequest::class, 'request_number'),
        ]);
    }

    public function consume(array $data): ConstructionMaterialConsumption
    {
        return $this->numbers->transaction(function () use ($data) {
            $material = ! empty($data['material_id'])
                ? ConstructionMaterial::findOrFail($data['material_id'])
                : null;

            $quantity = (float) ($data['actual_quantity'] ?? 0);
            if ($material && (float) $material->stock_quantity < $quantity) {
                throw ValidationException::withMessages(['actual_quantity' => 'Material consumption cannot exceed available stock.']);
            }

            if ($material) {
                $material->decrement('stock_quantity', $quantity);
            }

            return ConstructionMaterialConsumption::create([
                ...$data,
                'material_name' => $data['material_name'] ?? $material?->name ?? 'Material',
                'cost' => (float) ($data['cost'] ?? (($material?->unit_cost ?? 0) * $quantity)),
            ]);
        });
    }
}
