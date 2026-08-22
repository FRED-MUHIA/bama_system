<?php

namespace Modules\Agriculture\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Agriculture\Models\AgricultureDocument;
use Modules\Agriculture\Models\AgricultureInput;
use Modules\Agriculture\Models\Animal;
use Modules\Agriculture\Models\ComplianceRecord;
use Modules\Agriculture\Models\Crop;
use Modules\Agriculture\Models\CropPlan;
use Modules\Agriculture\Models\Equipment;
use Modules\Agriculture\Models\Farm;
use Modules\Agriculture\Models\FarmActivity;
use Modules\Agriculture\Models\Farmer;
use Modules\Agriculture\Models\FarmSeason;
use Modules\Agriculture\Models\FarmWorker;
use Modules\Agriculture\Models\Field;
use Modules\Agriculture\Models\Harvest;
use Modules\Agriculture\Models\Herd;
use Modules\Agriculture\Models\InputUsage;
use Modules\Agriculture\Models\IrrigationSchedule;
use Modules\Agriculture\Models\PestDiseaseIncident;
use Modules\Agriculture\Models\Plot;
use Modules\Agriculture\Models\ProduceBatch;
use Modules\Agriculture\Models\ProduceSale;
use Modules\Agriculture\Models\ProduceWarehouse;
use Modules\Agriculture\Services\AgricultureService;

class AgricultureOperationsController extends Controller
{
    public function store(Request $request, string $type, AgricultureService $service)
    {
        abort_unless($request->user()?->hasPermission($this->permissionFor($type)), 403, 'You do not have permission to manage this agriculture record.');

        $record = match ($type) {
            'farms' => $service->createFarm($request->validate($this->farmRules())),
            'branches' => $service->createBranch($request->validate($this->branchRules())),
            'zones' => $service->createZone($request->validate($this->zoneRules())),
            'fields' => $service->createField($request->validate($this->fieldRules())),
            'plots' => $service->createPlot($request->validate($this->plotRules())),
            'seasons' => $service->createSeason($request->validate($this->seasonRules())),
            'workers' => $service->createWorker($request->validate($this->workerRules())),
            'weather' => $service->createWeather($request->validate($this->weatherRules())),
            'crops' => $service->createCrop($request->validate($this->cropRules())),
            'crop-plans' => $service->createCropPlan($request->validate($this->cropPlanRules())),
            'activities' => $service->createActivity($request->validate($this->activityRules())),
            'harvests' => $service->createHarvest($request->validate($this->harvestRules())),
            'herds' => $service->createHerd($request->validate($this->herdRules())),
            'animals' => $service->createAnimal($request->validate($this->animalRules())),
            'veterinary' => $service->createVeterinaryRecord($request->validate($this->veterinaryRules())),
            'breeding' => $service->createBreedingEvent($request->validate($this->breedingRules())),
            'production' => $service->createProduction($request->validate($this->productionRules())),
            'feed-types' => $service->createFeedType($request->validate($this->feedTypeRules())),
            'feed-usage' => $service->createFeedUsage($request->validate($this->feedUsageRules())),
            'inputs' => $service->createInput($request->validate($this->inputRules())),
            'input-usage' => $service->createInputUsage($request->validate($this->inputUsageRules())),
            'fertilizers' => $service->createFertilizerApplication($request->validate($this->fertilizerRules())),
            'pest-disease' => $service->createPestIncident($request->validate($this->pestRules())),
            'irrigation' => $service->createIrrigation($request->validate($this->irrigationRules())),
            'equipment' => $service->createEquipment($request->validate($this->equipmentRules())),
            'equipment-maintenance' => $service->createEquipmentMaintenance($request->validate($this->equipmentMaintenanceRules())),
            'farmers' => $service->createFarmer($request->validate($this->farmerRules())),
            'contracts' => $service->createContract($request->validate($this->contractRules())),
            'warehouses' => $service->createWarehouse($request->validate($this->warehouseRules())),
            'bins' => $service->createStorageBin($request->validate($this->binRules())),
            'warehouse-movements' => $service->createWarehouseMovement($request->validate($this->movementRules())),
            'sales' => $service->createProduceSale($request->validate($this->saleRules())),
            'compliance' => $service->createCompliance($request->validate($this->complianceRules()), $request->file('attachment')),
            'budgets' => $service->createBudget($request->validate($this->budgetRules())),
            'documents' => $service->createDocument($this->documentData($request), $request->file('file')),
            default => abort(404),
        };

        return redirect()->route('agriculture.dashboard', ['section' => $this->sectionFor($type)])->with('status', class_basename($record).' saved.');
    }

    public function downloadDocument(AgricultureDocument $document)
    {
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path);
    }

    public function destroyDocument(AgricultureDocument $document, AgricultureService $service)
    {
        $service->deleteDocument($document);

        return back()->with('status', 'Agriculture document deleted.');
    }

    private function documentData(Request $request): array
    {
        $data = $request->validate([
            'farm_id' => ['nullable', $this->exists('agriculture_farms')],
            'documentable_type' => ['nullable', Rule::in(array_keys($this->documentables()))],
            'documentable_id' => ['nullable', 'integer', 'min:1'],
            'document_template_id' => ['nullable', $this->exists('document_templates')],
            'document_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Draft,Active,Expired,Archived'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        if (! empty($data['documentable_type']) && ! empty($data['documentable_id'])) {
            $model = $this->documentables()[$data['documentable_type']]::whereKey($data['documentable_id'])->firstOrFail();
            $data['documentable_type'] = $model->getMorphClass();
            $data['documentable_id'] = $model->getKey();
            $data['farm_id'] = $data['farm_id'] ?? ($model->farm_id ?? null);
        }

        unset($data['file']);

        return $data;
    }

    private function exists(string $table)
    {
        return Rule::exists($table, 'id')
            ->where('business_id', ActiveBusiness::id());
    }

    private function farmRules(): array { return ['farm_code' => ['nullable', 'string', 'max:50'], 'name' => ['required', 'string', 'max:255'], 'location' => ['nullable', 'string'], 'gps_coordinates' => ['nullable', 'string'], 'county_region' => ['nullable', 'string'], 'total_area' => ['required', 'numeric', 'min:0'], 'measurement_unit' => ['required', 'in:Acres,Hectares,Sq Ft,Sq M'], 'ownership_type' => ['required', 'in:Owned,Leased,Contracted,Cooperative,Community'], 'manager_id' => ['nullable', 'exists:users,id'], 'description' => ['nullable', 'string'], 'status' => ['required', 'in:Active,Inactive,Seasonal,Under Development']]; }
    private function branchRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'branch_code' => ['nullable', 'string', 'max:50'], 'branch_id' => ['nullable', $this->exists('branches')], 'name' => ['required', 'string', 'max:255'], 'location' => ['nullable', 'string'], 'manager_id' => ['nullable', 'exists:users,id'], 'status' => ['required', 'in:Active,Inactive']]; }
    private function zoneRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'agriculture_farm_branch_id' => ['nullable', $this->exists('agriculture_farm_branches')], 'zone_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'description' => ['nullable', 'string'], 'status' => ['required', 'in:Active,Inactive']]; }
    private function fieldRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'agriculture_farm_branch_id' => ['nullable', $this->exists('agriculture_farm_branches')], 'agriculture_farm_zone_id' => ['nullable', $this->exists('agriculture_farm_zones')], 'field_code' => ['nullable', 'string', 'max:50'], 'name' => ['required', 'string', 'max:255'], 'size' => ['required', 'numeric', 'min:0.0001'], 'measurement_unit' => ['required', 'in:Acres,Hectares,Sq Ft,Sq M'], 'soil_type' => ['nullable', 'string'], 'irrigation_type' => ['required', 'in:Drip,Sprinkler,Furrow,Pivot,Manual,Rain-fed'], 'latitude' => ['nullable', 'numeric'], 'longitude' => ['nullable', 'numeric'], 'gps_location' => ['nullable', 'string'], 'current_crop' => ['nullable', 'string'], 'previous_crop' => ['nullable', 'string'], 'status' => ['required', 'in:Available,Preparing,Planted,Growing,Harvesting,Fallow,Maintenance']]; }
    private function plotRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'field_id' => ['required', $this->exists('agriculture_fields')], 'plot_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'size' => ['required', 'numeric', 'min:0.0001'], 'soil_type' => ['nullable', 'string'], 'status' => ['required', 'in:Available,Preparing,Planted,Growing,Harvesting,Fallow,Maintenance']]; }
    private function seasonRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'season_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'rainfall_expectation' => ['nullable', 'string'], 'status' => ['required', 'in:Open,Closed,Planned']]; }
    private function workerRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'field_id' => ['nullable', $this->exists('agriculture_fields')], 'user_id' => ['nullable', 'exists:users,id'], 'worker_number' => ['nullable', 'string'], 'name' => ['required', 'string'], 'role_title' => ['required', 'string'], 'duties' => ['nullable', 'string'], 'work_schedule' => ['nullable', 'string'], 'activities_completed' => ['nullable', 'integer', 'min:0'], 'status' => ['required', 'in:Active,Inactive,Seasonal']]; }
    private function weatherRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'recorded_on' => ['required', 'date'], 'rainfall_mm' => ['nullable', 'numeric', 'min:0'], 'temperature_c' => ['nullable', 'numeric'], 'humidity_percent' => ['nullable', 'numeric', 'min:0', 'max:100'], 'wind_kph' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string']]; }
    private function cropRules(): array { return ['crop_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'category' => ['required', 'in:Cereals,Vegetables,Fruits,Legumes,Tubers,Cash Crops,Herbs,Flowers,Forage,Plantation Crops'], 'variety' => ['nullable', 'string'], 'expected_growing_period_days' => ['nullable', 'integer', 'min:0'], 'recommended_planting_season' => ['nullable', 'string'], 'expected_yield' => ['nullable', 'numeric', 'min:0'], 'yield_unit' => ['required', 'string'], 'notes' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']]; }
    private function cropPlanRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'field_id' => ['required', $this->exists('agriculture_fields')], 'season_id' => ['nullable', $this->exists('agriculture_farm_seasons')], 'crop_id' => ['required', $this->exists('agriculture_crops')], 'manager_id' => ['nullable', 'exists:users,id'], 'variety' => ['nullable', 'string'], 'planting_date' => ['nullable', 'date'], 'expected_germination_date' => ['nullable', 'date'], 'expected_harvest_date' => ['nullable', 'date'], 'planned_acreage' => ['nullable', 'numeric', 'min:0'], 'seed_quantity' => ['nullable', 'numeric', 'min:0'], 'expected_yield' => ['nullable', 'numeric', 'min:0'], 'budget' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'in:Draft,Approved,Preparing,Planted,Growing,Harvest Ready,Harvested,Closed,Cancelled']]; }
    private function activityRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'field_id' => ['nullable', $this->exists('agriculture_fields')], 'crop_plan_id' => ['nullable', $this->exists('agriculture_crop_plans')], 'assigned_worker_id' => ['nullable', $this->exists('agriculture_farm_workers')], 'equipment_id' => ['nullable', $this->exists('agriculture_equipment')], 'activity_type' => ['required', 'in:Land Preparation,Ploughing,Harrowing,Planting,Transplanting,Irrigation,Fertilizer Application,Pesticide Application,Herbicide Application,Weeding,Pruning,Harvesting,Transportation,Soil Testing,Inspection'], 'scheduled_date' => ['required', 'date'], 'completion_date' => ['nullable', 'date'], 'inputs_used' => ['nullable', 'string'], 'cost' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string'], 'status' => ['required', 'in:Planned,Assigned,In Progress,Completed,Cancelled,Overdue']]; }
    private function harvestRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'field_id' => ['required', $this->exists('agriculture_fields')], 'crop_plan_id' => ['nullable', $this->exists('agriculture_crop_plans')], 'crop_id' => ['nullable', $this->exists('agriculture_crops')], 'harvest_date' => ['required', 'date'], 'quantity' => ['required', 'numeric', 'min:0.0001'], 'measurement_unit' => ['required', 'string'], 'grade' => ['required', 'in:Grade A,Grade B,Grade C,Reject'], 'quality' => ['nullable', 'string'], 'waste_quantity' => ['nullable', 'numeric', 'min:0'], 'destination' => ['required', 'string'], 'storage_location' => ['nullable', 'string'], 'value' => ['nullable', 'numeric', 'min:0']]; }
    private function herdRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'herd_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'category' => ['required', 'in:Cattle,Dairy Cattle,Goats,Sheep,Poultry,Pigs,Rabbits,Fish,Bees,Other'], 'breed' => ['nullable', 'string'], 'animal_count' => ['nullable', 'integer', 'min:0'], 'status' => ['required', 'in:Active,Sold,Transferred,Closed']]; }
    private function animalRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'herd_id' => ['nullable', $this->exists('agriculture_herds')], 'animal_id' => ['nullable', 'string'], 'tag_number' => ['nullable', 'string'], 'name' => ['nullable', 'string'], 'species' => ['required', 'string'], 'breed' => ['nullable', 'string'], 'gender' => ['nullable', 'in:Male,Female,Unknown'], 'date_of_birth' => ['nullable', 'date'], 'acquisition_date' => ['nullable', 'date'], 'mother_id' => ['nullable', $this->exists('agriculture_animals')], 'father_id' => ['nullable', $this->exists('agriculture_animals')], 'weight' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'in:Active,Sold,Deceased,Transferred,Culled']]; }
    private function veterinaryRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'animal_id' => ['nullable', $this->exists('agriculture_animals')], 'herd_id' => ['nullable', $this->exists('agriculture_herds')], 'veterinarian_id' => ['nullable', 'exists:users,id'], 'record_type' => ['required', 'in:Vaccination,Disease Case,Treatment,Deworming,Pregnancy Check,Veterinary Review'], 'record_date' => ['required', 'date'], 'diagnosis' => ['nullable', 'string'], 'medication' => ['nullable', 'string'], 'treatment_cost' => ['nullable', 'numeric', 'min:0'], 'next_due_date' => ['nullable', 'date'], 'recovery_status' => ['required', 'in:Monitoring,Recovered,Follow-up,Closed'], 'notes' => ['nullable', 'string']]; }
    private function breedingRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'animal_id' => ['nullable', $this->exists('agriculture_animals')], 'herd_id' => ['nullable', $this->exists('agriculture_herds')], 'method' => ['required', 'in:Artificial Insemination,Natural Mating'], 'event_date' => ['required', 'date'], 'pregnancy_check_date' => ['nullable', 'date'], 'expected_birth_date' => ['nullable', 'date'], 'birth_date' => ['nullable', 'date'], 'offspring_count' => ['nullable', 'integer', 'min:0'], 'status' => ['required', 'in:Pending,Confirmed,Failed,Birthed,Closed'], 'notes' => ['nullable', 'string']]; }
    private function productionRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'animal_id' => ['nullable', $this->exists('agriculture_animals')], 'herd_id' => ['nullable', $this->exists('agriculture_herds')], 'production_type' => ['required', 'in:Milk,Eggs,Honey,Fish Stock,Animal Weight'], 'production_date' => ['required', 'date'], 'morning_quantity' => ['nullable', 'numeric', 'min:0'], 'evening_quantity' => ['nullable', 'numeric', 'min:0'], 'quantity' => ['nullable', 'numeric', 'min:0'], 'damaged_quantity' => ['nullable', 'numeric', 'min:0'], 'sold_quantity' => ['nullable', 'numeric', 'min:0'], 'measurement_unit' => ['required', 'string'], 'value' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string']]; }
    private function feedTypeRules(): array { return ['product_id' => ['nullable', $this->exists('products')], 'feed_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'category' => ['nullable', 'string'], 'unit' => ['required', 'string'], 'cost_per_unit' => ['nullable', 'numeric', 'min:0'], 'is_active' => ['nullable', 'boolean']]; }
    private function feedUsageRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'feed_type_id' => ['required', $this->exists('agriculture_feed_types')], 'animal_id' => ['nullable', $this->exists('agriculture_animals')], 'herd_id' => ['nullable', $this->exists('agriculture_herds')], 'usage_date' => ['required', 'date'], 'quantity' => ['required', 'numeric', 'min:0.0001'], 'cost' => ['nullable', 'numeric', 'min:0'], 'allocation_target' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]; }
    private function inputRules(): array { return ['product_id' => ['nullable', $this->exists('products')], 'supplier_id' => ['nullable', $this->exists('suppliers')], 'input_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'category' => ['required', 'in:Seeds,Seedlings,Fertilizers,Pesticides,Herbicides,Fungicides,Animal Feed,Veterinary Medicine,Fuel,Packaging,Irrigation Supplies,Farm Consumables'], 'batch_number' => ['nullable', 'string'], 'expiry_date' => ['nullable', 'date'], 'application_rate' => ['nullable', 'numeric', 'min:0'], 'unit' => ['required', 'string'], 'safety_period_days' => ['nullable', 'integer', 'min:0'], 'storage_conditions' => ['nullable', 'string'], 'quantity_on_hand' => ['nullable', 'numeric', 'min:0'], 'unit_cost' => ['nullable', 'numeric', 'min:0'], 'reorder_level' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'in:Active,Inactive,Expired,Quarantined']]; }
    private function inputUsageRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'input_id' => ['required', $this->exists('agriculture_inputs')], 'field_id' => ['nullable', $this->exists('agriculture_fields')], 'crop_plan_id' => ['nullable', $this->exists('agriculture_crop_plans')], 'activity_id' => ['nullable', $this->exists('agriculture_farm_activities')], 'worker_id' => ['nullable', $this->exists('agriculture_farm_workers')], 'usage_date' => ['required', 'date'], 'quantity_used' => ['required', 'numeric', 'min:0.0001'], 'cost' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string']]; }
    private function fertilizerRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'field_id' => ['required', $this->exists('agriculture_fields')], 'crop_id' => ['nullable', $this->exists('agriculture_crops')], 'fertilizer_type' => ['required', 'string'], 'application_rate' => ['nullable', 'numeric', 'min:0'], 'application_date' => ['required', 'date'], 'quantity' => ['required', 'numeric', 'min:0.0001'], 'cost' => ['nullable', 'numeric', 'min:0'], 'application_method' => ['nullable', 'string']]; }
    private function pestRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'field_id' => ['nullable', $this->exists('agriculture_fields')], 'crop_id' => ['nullable', $this->exists('agriculture_crops')], 'name' => ['required', 'string'], 'severity' => ['required', 'in:Low,Moderate,High,Critical'], 'observation_date' => ['required', 'date'], 'photos' => ['nullable', 'string'], 'recommended_action' => ['nullable', 'string'], 'chemical_used' => ['nullable', 'string'], 'treatment' => ['nullable', 'string'], 'follow_up_date' => ['nullable', 'date'], 'status' => ['required', 'in:Open,Treating,Resolved,Closed']]; }
    private function irrigationRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'field_id' => ['required', $this->exists('agriculture_fields')], 'operator_id' => ['nullable', 'exists:users,id'], 'irrigation_type' => ['required', 'in:Drip,Sprinkler,Furrow,Pivot,Manual,Rain-fed'], 'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'water_quantity' => ['nullable', 'numeric', 'min:0'], 'pump' => ['nullable', 'string'], 'cost' => ['nullable', 'numeric', 'min:0'], 'iot_reference' => ['nullable', 'string'], 'status' => ['required', 'in:Scheduled,Running,Completed,Cancelled']]; }
    private function equipmentRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'equipment_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'equipment_type' => ['required', 'in:Tractor,Plough,Harvester,Sprayer,Irrigation Pump,Generator,Milking Machine,Vehicle,Trailer,Other'], 'serial_number' => ['nullable', 'string'], 'purchase_date' => ['nullable', 'date'], 'purchase_cost' => ['nullable', 'numeric', 'min:0'], 'current_value' => ['nullable', 'numeric', 'min:0'], 'fuel_type' => ['nullable', 'string'], 'assigned_operator_id' => ['nullable', 'exists:users,id'], 'fixed_asset_id' => ['nullable', $this->exists('fixed_assets')], 'status' => ['required', 'in:Available,In Use,Under Maintenance,Broken Down,Retired']]; }
    private function equipmentMaintenanceRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'equipment_id' => ['required', $this->exists('agriculture_equipment')], 'technician_id' => ['nullable', 'exists:users,id'], 'service_date' => ['required', 'date'], 'service_type' => ['required', 'string'], 'parts_used' => ['nullable', 'string'], 'cost' => ['nullable', 'numeric', 'min:0'], 'next_service_date' => ['nullable', 'date'], 'meter_hours_reading' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string'], 'status' => ['required', 'in:Scheduled,Completed,Deferred']]; }
    private function farmerRules(): array { return ['client_id' => ['nullable', $this->exists('clients')], 'name' => ['required', 'string'], 'phone' => ['nullable', 'string'], 'id_number' => ['nullable', 'string'], 'farm_location' => ['nullable', 'string'], 'acreage' => ['nullable', 'numeric', 'min:0'], 'crops' => ['nullable', 'string'], 'input_advances' => ['nullable', 'numeric', 'min:0'], 'deliveries_value' => ['nullable', 'numeric', 'min:0'], 'payments_value' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'in:Active,Inactive,Suspended']]; }
    private function contractRules(): array { return ['farmer_id' => ['required', $this->exists('agriculture_farmers')], 'crop_id' => ['nullable', $this->exists('agriculture_crops')], 'contracting_company' => ['nullable', 'string'], 'acreage' => ['nullable', 'numeric', 'min:0'], 'inputs_provided' => ['nullable', 'string'], 'expected_quantity' => ['nullable', 'numeric', 'min:0'], 'agreed_price' => ['nullable', 'numeric', 'min:0'], 'delivery_dates' => ['nullable', 'string'], 'status' => ['required', 'in:Draft,Active,Fulfilled,Cancelled']]; }
    private function warehouseRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'warehouse_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'warehouse_type' => ['required', 'string'], 'location' => ['nullable', 'string'], 'capacity' => ['nullable', 'numeric', 'min:0'], 'temperature_control' => ['nullable', 'string'], 'status' => ['required', 'in:Active,Inactive,Full']]; }
    private function binRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'warehouse_id' => ['required', $this->exists('agriculture_produce_warehouses')], 'bin_code' => ['nullable', 'string'], 'name' => ['required', 'string'], 'capacity' => ['nullable', 'numeric', 'min:0'], 'quality' => ['nullable', 'string'], 'temperature_c' => ['nullable', 'numeric'], 'status' => ['required', 'in:Available,Occupied,Maintenance']]; }
    private function movementRules(): array { return ['farm_id' => ['nullable', $this->exists('agriculture_farms')], 'produce_batch_id' => ['required', $this->exists('agriculture_produce_batches')], 'warehouse_id' => ['nullable', $this->exists('agriculture_produce_warehouses')], 'storage_bin_id' => ['nullable', $this->exists('agriculture_storage_bins')], 'movement_type' => ['required', 'in:Stored,Transferred,Processed,Sold,Disposed'], 'movement_date' => ['required', 'date'], 'quantity' => ['required', 'numeric', 'min:0.0001'], 'loss_quantity' => ['nullable', 'numeric', 'min:0'], 'quality' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]; }
    private function saleRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'client_id' => ['nullable', $this->exists('clients')], 'produce_batch_id' => ['nullable', $this->exists('agriculture_produce_batches')], 'buyer_type' => ['required', 'in:Individual,Retailer,Wholesaler,Processor,Exporter,Cooperative,Government Agency'], 'sale_date' => ['required', 'date'], 'quantity' => ['required', 'numeric', 'min:0.0001'], 'unit_price' => ['required', 'numeric', 'min:0'], 'total' => ['nullable', 'numeric', 'min:0'], 'delivery_status' => ['required', 'in:Pending,Delivered,Cancelled'], 'payment_status' => ['required', 'in:Unpaid,Partial,Paid']]; }
    private function complianceRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'compliance_type' => ['required', 'string'], 'certificate_number' => ['nullable', 'string'], 'issue_date' => ['nullable', 'date'], 'expiry_date' => ['nullable', 'date'], 'attachment' => ['nullable', 'file', 'max:10240'], 'status' => ['required', 'in:Active,Expired,Pending,Revoked']]; }
    private function budgetRules(): array { return ['farm_id' => ['required', $this->exists('agriculture_farms')], 'field_id' => ['nullable', $this->exists('agriculture_fields')], 'crop_plan_id' => ['nullable', $this->exists('agriculture_crop_plans')], 'animal_id' => ['nullable', $this->exists('agriculture_animals')], 'equipment_id' => ['nullable', $this->exists('agriculture_equipment')], 'budget_type' => ['required', 'string'], 'category' => ['required', 'string'], 'fiscal_year' => ['required', 'integer', 'min:2000'], 'budget_amount' => ['nullable', 'numeric', 'min:0'], 'actual_amount' => ['nullable', 'numeric', 'min:0'], 'alert_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'], 'status' => ['required', 'in:Open,Closed']]; }

    private function documentables(): array
    {
        return ['farm' => Farm::class, 'field' => Field::class, 'plot' => Plot::class, 'season' => FarmSeason::class, 'crop' => Crop::class, 'crop-plan' => CropPlan::class, 'activity' => FarmActivity::class, 'harvest' => Harvest::class, 'produce-batch' => ProduceBatch::class, 'animal' => Animal::class, 'herd' => Herd::class, 'input' => AgricultureInput::class, 'input-usage' => InputUsage::class, 'equipment' => Equipment::class, 'worker' => FarmWorker::class, 'warehouse' => ProduceWarehouse::class, 'sale' => ProduceSale::class, 'farmer' => Farmer::class, 'compliance' => ComplianceRecord::class, 'pest-disease' => PestDiseaseIncident::class, 'irrigation' => IrrigationSchedule::class];
    }

    private function sectionFor(string $type): string
    {
        return match ($type) {
            'farms', 'branches', 'zones', 'fields', 'plots', 'seasons', 'weather' => 'farms',
            'crops', 'crop-plans', 'activities' => 'crops',
            'harvests' => 'harvest',
            'herds', 'animals', 'veterinary', 'breeding', 'production', 'feed-types', 'feed-usage' => 'livestock',
            'inputs', 'input-usage', 'fertilizers', 'pest-disease' => 'inputs',
            'irrigation' => 'irrigation',
            'equipment', 'equipment-maintenance' => 'equipment',
            'workers' => 'workers',
            'warehouses', 'bins', 'warehouse-movements' => 'storage',
            'sales', 'farmers', 'contracts' => 'sales',
            'compliance' => 'compliance',
            'budgets' => 'finance',
            'documents' => 'documents',
            default => 'dashboard',
        };
    }

    private function permissionFor(string $type): string
    {
        return match ($type) {
            'farms', 'branches', 'zones', 'seasons', 'weather' => 'farms.manage',
            'fields', 'plots', 'irrigation' => 'fields.manage',
            'crops' => 'crops.manage',
            'crop-plans' => 'crop_plans.manage',
            'activities' => 'farm_activities.manage',
            'harvests', 'warehouses', 'bins', 'warehouse-movements' => 'harvests.manage',
            'herds', 'animals', 'production', 'feed-types', 'feed-usage' => 'livestock.manage',
            'veterinary' => 'veterinary.manage',
            'breeding' => 'breeding.manage',
            'inputs', 'input-usage', 'fertilizers', 'pest-disease' => 'inputs.manage',
            'equipment', 'equipment-maintenance' => 'equipment.manage',
            'sales', 'budgets' => 'agriculture.finance',
            'farmers', 'contracts' => 'agriculture.procurement',
            'compliance' => 'agriculture.settings',
            'documents' => 'agriculture.documents.manage',
            default => 'agriculture.manage',
        };
    }
}
