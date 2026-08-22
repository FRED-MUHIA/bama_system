<?php

namespace Modules\Agriculture\Services;

use Illuminate\Support\Collection;
use Modules\Agriculture\Models\Animal;
use Modules\Agriculture\Models\BudgetLine;
use Modules\Agriculture\Models\ComplianceRecord;
use Modules\Agriculture\Models\CropPlan;
use Modules\Agriculture\Models\Equipment;
use Modules\Agriculture\Models\EquipmentMaintenance;
use Modules\Agriculture\Models\Farm;
use Modules\Agriculture\Models\FarmActivity;
use Modules\Agriculture\Models\Field;
use Modules\Agriculture\Models\Harvest;
use Modules\Agriculture\Models\InputUsage;
use Modules\Agriculture\Models\ProduceSale;
use Modules\Agriculture\Models\VeterinaryRecord;

class AgricultureReportingService
{
    public function rows(string $type): Collection
    {
        return match ($type) {
            'farms' => Farm::with('manager')->get()->map(fn ($farm) => ['farm_code' => $farm->farm_code, 'name' => $farm->name, 'area' => $farm->total_area, 'unit' => $farm->measurement_unit, 'manager' => $farm->manager?->name, 'status' => $farm->status]),
            'fields' => Field::with('farm')->get()->map(fn ($field) => ['field_code' => $field->field_code, 'farm' => $field->farm?->name, 'name' => $field->name, 'size' => $field->size, 'crop' => $field->current_crop, 'status' => $field->status]),
            'crop-plans' => CropPlan::with('farm', 'field', 'crop')->get()->map(fn ($plan) => ['plan_number' => $plan->plan_number, 'farm' => $plan->farm?->name, 'field' => $plan->field?->name, 'crop' => $plan->crop?->name, 'planting_date' => $plan->planting_date?->toDateString(), 'expected_harvest_date' => $plan->expected_harvest_date?->toDateString(), 'expected_yield' => $plan->expected_yield, 'status' => $plan->status]),
            'activities' => FarmActivity::with('farm', 'field', 'cropPlan.crop', 'worker')->get()->map(fn ($activity) => ['activity_number' => $activity->activity_number, 'farm' => $activity->farm?->name, 'field' => $activity->field?->name, 'crop_plan' => $activity->cropPlan?->plan_number, 'crop' => $activity->cropPlan?->crop?->name, 'activity_type' => $activity->activity_type, 'scheduled_date' => $activity->scheduled_date?->toDateString(), 'status' => $activity->status, 'cost' => $activity->cost]),
            'harvests' => Harvest::with('farm', 'field', 'crop')->get()->map(fn ($harvest) => ['harvest_number' => $harvest->harvest_number, 'farm' => $harvest->farm?->name, 'field' => $harvest->field?->name, 'crop' => $harvest->crop?->name, 'quantity' => $harvest->quantity, 'waste' => $harvest->waste_quantity, 'value' => $harvest->value]),
            'livestock' => Animal::with('farm', 'herd')->get()->map(fn ($animal) => ['animal_id' => $animal->animal_id, 'tag_number' => $animal->tag_number, 'farm' => $animal->farm?->name, 'herd' => $animal->herd?->name, 'species' => $animal->species, 'breed' => $animal->breed, 'status' => $animal->status]),
            'veterinary' => VeterinaryRecord::with('farm', 'animal', 'herd', 'veterinarian')->get()->map(fn ($record) => ['record_number' => $record->record_number, 'farm' => $record->farm?->name, 'animal' => $record->animal?->animal_id, 'herd' => $record->herd?->name, 'record_type' => $record->record_type, 'record_date' => $record->record_date?->toDateString(), 'next_due_date' => $record->next_due_date?->toDateString(), 'cost' => $record->treatment_cost, 'status' => $record->recovery_status]),
            'inputs' => InputUsage::with('farm', 'field', 'cropPlan.crop', 'input')->get()->map(fn ($usage) => ['usage_number' => $usage->usage_number, 'input' => $usage->input?->name, 'farm' => $usage->farm?->name, 'field' => $usage->field?->name, 'crop_plan' => $usage->cropPlan?->plan_number, 'quantity' => $usage->quantity_used, 'cost' => $usage->cost, 'date' => $usage->usage_date?->toDateString()]),
            'equipment' => Equipment::with('farm', 'operator')->get()->map(fn ($equipment) => ['equipment_code' => $equipment->equipment_code, 'farm' => $equipment->farm?->name, 'name' => $equipment->name, 'type' => $equipment->equipment_type, 'operator' => $equipment->operator?->name, 'status' => $equipment->status]),
            'equipment-maintenance' => EquipmentMaintenance::with('equipment', 'farm')->get()->map(fn ($row) => ['maintenance_number' => $row->maintenance_number, 'equipment' => $row->equipment?->name, 'farm' => $row->farm?->name, 'service_date' => $row->service_date?->toDateString(), 'next_service_date' => $row->next_service_date?->toDateString(), 'cost' => $row->cost, 'status' => $row->status]),
            'sales' => ProduceSale::with('client', 'produceBatch')->get()->map(fn ($sale) => ['sale_number' => $sale->sale_number, 'buyer' => $sale->client?->name, 'buyer_type' => $sale->buyer_type, 'batch' => $sale->produceBatch?->batch_number, 'quantity' => $sale->quantity, 'total' => $sale->total, 'payment_status' => $sale->payment_status]),
            'finance' => BudgetLine::with('farm', 'field', 'cropPlan.crop')->get()->map(fn ($line) => ['budget_number' => $line->budget_number, 'type' => $line->budget_type, 'category' => $line->category, 'farm' => $line->farm?->name, 'field' => $line->field?->name, 'budget' => $line->budget_amount, 'actual' => $line->actual_amount, 'variance' => (float) $line->budget_amount - (float) $line->actual_amount]),
            'compliance' => ComplianceRecord::with('farm')->get()->map(fn ($record) => ['compliance_number' => $record->compliance_number, 'farm' => $record->farm?->name, 'type' => $record->compliance_type, 'certificate' => $record->certificate_number, 'expiry_date' => $record->expiry_date?->toDateString(), 'status' => $record->status]),
            default => collect(),
        };
    }
}
