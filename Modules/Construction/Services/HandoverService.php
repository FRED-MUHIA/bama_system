<?php

namespace Modules\Construction\Services;

use Modules\Construction\Models\ConstructionHandover;

class HandoverService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function create(array $data): ConstructionHandover
    {
        return ConstructionHandover::create([
            ...$data,
            'handover_number' => $data['handover_number'] ?? $this->numbers->next('HND', ConstructionHandover::class, 'handover_number'),
        ]);
    }
}
