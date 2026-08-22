<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\Waste;

class WasteService
{
    public function record(array $data): Waste
    {
        return Waste::create($data);
    }

    public function percentage(float $wasteQuantity, float $producedQuantity): float
    {
        $total = $wasteQuantity + $producedQuantity;

        return $total > 0 ? round(($wasteQuantity / $total) * 100, 2) : 0.0;
    }
}
