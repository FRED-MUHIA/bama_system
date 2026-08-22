<?php

namespace Modules\Construction\Services;

use Modules\Construction\Models\ConstructionRfi;
use Modules\Construction\Models\ConstructionSiteDiary;
use Modules\Construction\Models\ConstructionSiteInstruction;
use Modules\Construction\Models\ConstructionSiteReport;

class SiteManagementService
{
    public function __construct(private ConstructionNumberService $numbers) {}

    public function dailyReport(array $data): ConstructionSiteReport
    {
        return ConstructionSiteReport::create([
            ...$data,
            'report_number' => $data['report_number'] ?? $this->numbers->next('SDR', ConstructionSiteReport::class, 'report_number'),
        ]);
    }

    public function diary(array $data): ConstructionSiteDiary
    {
        return ConstructionSiteDiary::create($data);
    }

    public function rfi(array $data): ConstructionRfi
    {
        return ConstructionRfi::create([
            ...$data,
            'rfi_number' => $data['rfi_number'] ?? $this->numbers->next('RFI', ConstructionRfi::class, 'rfi_number'),
        ]);
    }

    public function instruction(array $data): ConstructionSiteInstruction
    {
        return ConstructionSiteInstruction::create([
            ...$data,
            'instruction_number' => $data['instruction_number'] ?? $this->numbers->next('SI', ConstructionSiteInstruction::class, 'instruction_number'),
        ]);
    }
}
