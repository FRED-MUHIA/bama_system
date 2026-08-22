<?php

namespace Modules\Construction\Services;

use Modules\Construction\Models\ConstructionContractor;
use Modules\Construction\Models\ConstructionSubcontract;

class ContractorService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function contractor(array $data): ConstructionContractor
    {
        return ConstructionContractor::create($data);
    }

    public function subcontract(array $data): ConstructionSubcontract
    {
        return ConstructionSubcontract::create([
            ...$data,
            'subcontract_number' => $data['subcontract_number'] ?? $this->numbers->next('SC', ConstructionSubcontract::class, 'subcontract_number'),
        ]);
    }
}
