<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\Machine;
use Modules\PrintingBranding\Models\MachineMaintenance;

class MachineService
{
    public function create(array $data): Machine
    {
        return Machine::create($data);
    }

    public function recordMaintenance(Machine $machine, array $data): MachineMaintenance
    {
        $record = MachineMaintenance::create($data + ['machine_id' => $machine->id]);

        if (in_array($machine->status, ['Available', 'In Use'], true)) {
            $machine->update(['status' => 'Maintenance']);
        }

        return $record;
    }
}
