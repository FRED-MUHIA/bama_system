<?php

namespace Modules\Agriculture\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\DocumentService;
use App\Services\StockService;
use App\Support\ActiveTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Agriculture\Models\AgricultureDocument;
use Modules\Agriculture\Models\AgricultureInput;
use Modules\Agriculture\Models\Animal;
use Modules\Agriculture\Models\BreedingEvent;
use Modules\Agriculture\Models\BudgetLine;
use Modules\Agriculture\Models\ComplianceRecord;
use Modules\Agriculture\Models\Crop;
use Modules\Agriculture\Models\CropPlan;
use Modules\Agriculture\Models\Equipment;
use Modules\Agriculture\Models\EquipmentMaintenance;
use Modules\Agriculture\Models\Farm;
use Modules\Agriculture\Models\FarmActivity;
use Modules\Agriculture\Models\Farmer;
use Modules\Agriculture\Models\FarmerContract;
use Modules\Agriculture\Models\FarmSeason;
use Modules\Agriculture\Models\FarmWorker;
use Modules\Agriculture\Models\FarmZone;
use Modules\Agriculture\Models\FeedType;
use Modules\Agriculture\Models\FeedUsage;
use Modules\Agriculture\Models\FertilizerApplication;
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
use Modules\Agriculture\Models\StorageBin;
use Modules\Agriculture\Models\VeterinaryRecord;
use Modules\Agriculture\Models\WarehouseMovement;
use Modules\Agriculture\Models\WeatherRecord;

class AgricultureService
{
    public function __construct(
        protected AgricultureNumberService $numbers,
        protected DocumentService $documents,
        protected StockService $stock,
    ) {}

    public function createFarm(array $data): Farm
    {
        $data['farm_code'] ??= $this->numbers->next('agriculture_farms', 'farm_code', 'FARM');
        $data = $this->zero($data, ['total_area']);

        return Farm::create($data);
    }

    public function createBranch(array $data): \Modules\Agriculture\Models\FarmBranch
    {
        $data['branch_code'] ??= $this->numbers->next('agriculture_farm_branches', 'branch_code', 'FBR');

        return \Modules\Agriculture\Models\FarmBranch::create($data);
    }

    public function createZone(array $data): FarmZone
    {
        $data['zone_code'] ??= $this->numbers->next('agriculture_farm_zones', 'zone_code', 'ZON');

        return FarmZone::create($data);
    }

    public function createField(array $data): Field
    {
        $farm = Farm::findOrFail($data['farm_id']);
        $size = (float) ($data['size'] ?? 0);
        if ($size <= 0) {
            throw ValidationException::withMessages(['size' => 'Field acreage must be greater than zero.']);
        }
        $allocated = (float) Field::where('farm_id', $farm->id)->sum('size');
        if ((float) $farm->total_area > 0 && $allocated + $size > (float) $farm->total_area + 0.0001) {
            throw ValidationException::withMessages(['size' => 'Field acreage exceeds the farm total area.']);
        }

        $data['field_code'] ??= $this->numbers->next('agriculture_fields', 'field_code', 'FLD');

        return Field::create($data);
    }

    public function createPlot(array $data): Plot
    {
        $field = Field::findOrFail($data['field_id']);
        $size = (float) ($data['size'] ?? 0);
        if ($size <= 0) {
            throw ValidationException::withMessages(['size' => 'Plot acreage must be greater than zero.']);
        }
        $allocated = (float) Plot::where('field_id', $field->id)->sum('size');
        if ((float) $field->size > 0 && $allocated + $size > (float) $field->size + 0.0001) {
            throw ValidationException::withMessages(['size' => 'Plot acreage exceeds the field size.']);
        }

        $data['farm_id'] = $data['farm_id'] ?? $field->farm_id;
        $data['plot_code'] ??= $this->numbers->next('agriculture_plots', 'plot_code', 'PLT');

        return Plot::create($data);
    }

    public function createSeason(array $data): FarmSeason
    {
        $data['season_code'] ??= $this->numbers->next('agriculture_farm_seasons', 'season_code', 'SEA');

        return FarmSeason::create($data);
    }

    public function createWorker(array $data): FarmWorker
    {
        $data['worker_number'] ??= $this->numbers->next('agriculture_farm_workers', 'worker_number', 'WRK');
        $data['duties'] = $this->list($data['duties'] ?? null);
        $data['work_schedule'] = $this->list($data['work_schedule'] ?? null);

        return FarmWorker::create($data);
    }

    public function createWeather(array $data): WeatherRecord
    {
        return WeatherRecord::create($this->zero($data, ['rainfall_mm', 'temperature_c', 'humidity_percent', 'wind_kph']));
    }

    public function createCrop(array $data): Crop
    {
        $data['crop_code'] ??= $this->numbers->next('agriculture_crops', 'crop_code', 'CRP');
        $data = $this->zero($data, ['expected_growing_period_days', 'expected_yield']);

        return Crop::create($data);
    }

    public function createCropPlan(array $data): CropPlan
    {
        $field = Field::findOrFail($data['field_id']);
        $openStatuses = ['Draft', 'Approved', 'Preparing', 'Planted', 'Growing', 'Harvest Ready'];
        if (CropPlan::where('field_id', $field->id)->where('crop_id', $data['crop_id'])->whereIn('status', $openStatuses)->exists()) {
            throw ValidationException::withMessages(['crop_id' => 'An open crop plan already exists for this field and crop.']);
        }

        $data['farm_id'] = $data['farm_id'] ?? $field->farm_id;
        $data['plan_number'] ??= $this->numbers->next('agriculture_crop_plans', 'plan_number', 'CP');
        $data = $this->zero($data, ['planned_acreage', 'seed_quantity', 'expected_yield', 'budget']);
        $plan = CropPlan::create($data);
        if (in_array($plan->status, ['Preparing', 'Planted', 'Growing', 'Harvest Ready'], true)) {
            $field->update(['status' => $plan->status === 'Preparing' ? 'Preparing' : 'Planted', 'current_crop' => $plan->crop?->name]);
        }

        return $plan;
    }

    public function createActivity(array $data): FarmActivity
    {
        if (! empty($data['crop_plan_id'])) {
            $plan = CropPlan::findOrFail($data['crop_plan_id']);
            $data['farm_id'] = $data['farm_id'] ?? $plan->farm_id;
            $data['field_id'] = $data['field_id'] ?? $plan->field_id;
        }
        $data['activity_number'] ??= $this->numbers->next('agriculture_farm_activities', 'activity_number', 'ACT');
        $data['inputs_used'] = $this->list($data['inputs_used'] ?? null);
        $data = $this->zero($data, ['cost']);

        return FarmActivity::create($data);
    }

    public function createHarvest(array $data): Harvest
    {
        if ((float) ($data['quantity'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Harvest quantity must be greater than zero.']);
        }

        if (! empty($data['crop_plan_id'])) {
            $plan = CropPlan::findOrFail($data['crop_plan_id']);
            $data['farm_id'] = $data['farm_id'] ?? $plan->farm_id;
            $data['field_id'] = $data['field_id'] ?? $plan->field_id;
            $data['crop_id'] = $data['crop_id'] ?? $plan->crop_id;
            $data['expected_yield'] = $data['expected_yield'] ?? $plan->expected_yield;
        }

        $data['harvest_number'] ??= $this->numbers->next('agriculture_harvests', 'harvest_number', 'HAR');
        $data = $this->zero($data, ['waste_quantity', 'expected_yield', 'value']);

        return DB::transaction(function () use ($data) {
            $harvest = Harvest::create($data);
            $this->createProduceBatch([
                'farm_id' => $harvest->farm_id,
                'harvest_id' => $harvest->id,
                'quantity' => $harvest->quantity,
                'measurement_unit' => $harvest->measurement_unit,
                'grade' => $harvest->grade,
                'storage_location' => $harvest->storage_location,
                'date_received' => $harvest->harvest_date,
            ]);
            $harvest->cropPlan?->update(['status' => 'Harvested']);

            return $harvest;
        });
    }

    public function createProduceBatch(array $data): ProduceBatch
    {
        $data['batch_number'] ??= $this->numbers->next('agriculture_produce_batches', 'batch_number', 'PRD');
        $data['traceability_id'] ??= 'AGR-'.ActiveTenant::id().'-'.$data['batch_number'];
        $data['date_received'] ??= now()->toDateString();
        $data = $this->zero($data, ['quantity']);

        return ProduceBatch::create($data);
    }

    public function createHerd(array $data): Herd
    {
        $data['herd_code'] ??= $this->numbers->next('agriculture_herds', 'herd_code', 'HRD');

        return Herd::create($data);
    }

    public function createAnimal(array $data): Animal
    {
        $data['animal_id'] ??= $this->numbers->next('agriculture_animals', 'animal_id', 'ANM');
        $data = $this->zero($data, ['weight']);
        $animal = Animal::create($data);
        $animal->herd?->update(['animal_count' => Animal::where('herd_id', $animal->herd_id)->where('status', 'Active')->count()]);

        return $animal;
    }

    public function createVeterinaryRecord(array $data): VeterinaryRecord
    {
        if (! empty($data['animal_id'])) {
            $animal = Animal::findOrFail($data['animal_id']);
            $data['farm_id'] = $data['farm_id'] ?? $animal->farm_id;
            $data['herd_id'] = $data['herd_id'] ?? $animal->herd_id;
        }
        $data['record_number'] ??= $this->numbers->next('agriculture_veterinary_records', 'record_number', 'VET');
        $data = $this->zero($data, ['treatment_cost']);

        return VeterinaryRecord::create($data);
    }

    public function createBreedingEvent(array $data): BreedingEvent
    {
        if (! empty($data['animal_id'])) {
            $animal = Animal::findOrFail($data['animal_id']);
            $data['farm_id'] = $data['farm_id'] ?? $animal->farm_id;
            $data['herd_id'] = $data['herd_id'] ?? $animal->herd_id;
        }
        $data['event_number'] ??= $this->numbers->next('agriculture_breeding_events', 'event_number', 'BRD');
        $data = $this->zero($data, ['offspring_count']);

        return BreedingEvent::create($data);
    }

    public function createProduction(array $data): \Modules\Agriculture\Models\LivestockProduction
    {
        if (! empty($data['animal_id'])) {
            $animal = Animal::findOrFail($data['animal_id']);
            $data['farm_id'] = $data['farm_id'] ?? $animal->farm_id;
            $data['herd_id'] = $data['herd_id'] ?? $animal->herd_id;
        }
        $data['production_number'] ??= $this->numbers->next('agriculture_livestock_productions', 'production_number', 'LPR');
        $data = $this->zero($data, ['morning_quantity', 'evening_quantity', 'damaged_quantity', 'sold_quantity', 'value']);
        $data['quantity'] = $data['quantity'] ?? ((float) $data['morning_quantity'] + (float) $data['evening_quantity']);

        return \Modules\Agriculture\Models\LivestockProduction::create($data);
    }

    public function createFeedType(array $data): FeedType
    {
        $data['feed_code'] ??= $this->numbers->next('agriculture_feed_types', 'feed_code', 'FED');
        $data = $this->zero($data, ['cost_per_unit']);

        return FeedType::create($data);
    }

    public function createFeedUsage(array $data): FeedUsage
    {
        $feed = FeedType::findOrFail($data['feed_type_id']);
        if ($feed->product_id) {
            $product = Product::findOrFail($feed->product_id);
            $this->assertStock($product, (float) $data['quantity']);
            $this->stock->consume($product, (float) $data['quantity'], null, 'Agriculture feed usage');
        }
        $data['cost'] = $data['cost'] ?? ((float) $data['quantity'] * (float) $feed->cost_per_unit);

        return FeedUsage::create($data);
    }

    public function createInput(array $data): AgricultureInput
    {
        $data['input_code'] ??= $this->numbers->next('agriculture_inputs', 'input_code', 'INP');
        $data = $this->zero($data, ['application_rate', 'safety_period_days', 'quantity_on_hand', 'unit_cost', 'reorder_level']);

        return AgricultureInput::create($data);
    }

    public function createInputUsage(array $data): InputUsage
    {
        $input = AgricultureInput::findOrFail($data['input_id']);
        $quantity = (float) $data['quantity_used'];
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity_used' => 'Input usage quantity must be greater than zero.']);
        }
        if ((float) $input->quantity_on_hand < $quantity) {
            throw ValidationException::withMessages(['quantity_used' => 'Input stock cannot go negative.']);
        }

        if (! empty($data['crop_plan_id'])) {
            $plan = CropPlan::findOrFail($data['crop_plan_id']);
            $data['farm_id'] = $data['farm_id'] ?? $plan->farm_id;
            $data['field_id'] = $data['field_id'] ?? $plan->field_id;
        }
        $data['usage_number'] ??= $this->numbers->next('agriculture_input_usages', 'usage_number', 'IUS');
        $data['cost'] = $data['cost'] ?? ($quantity * (float) $input->unit_cost);

        return DB::transaction(function () use ($input, $data, $quantity) {
            $input = AgricultureInput::whereKey($input->id)->lockForUpdate()->firstOrFail();
            $input->update(['quantity_on_hand' => (float) $input->quantity_on_hand - $quantity]);
            if ($input->product_id) {
                $product = Product::findOrFail($input->product_id);
                $this->assertStock($product, $quantity);
                $this->stock->consume($product, $quantity, $input, 'Agriculture input usage');
            }

            return InputUsage::create($data);
        });
    }

    public function createFertilizerApplication(array $data): FertilizerApplication
    {
        return FertilizerApplication::create($this->zero($data, ['application_rate', 'quantity', 'cost']));
    }

    public function createPestIncident(array $data): PestDiseaseIncident
    {
        $data['incident_number'] ??= $this->numbers->next('agriculture_pest_disease_incidents', 'incident_number', 'PST');
        $data['photos'] = $this->list($data['photos'] ?? null);

        return PestDiseaseIncident::create($data);
    }

    public function createIrrigation(array $data): IrrigationSchedule
    {
        $data['schedule_number'] ??= $this->numbers->next('agriculture_irrigation_schedules', 'schedule_number', 'IRR');
        $data = $this->zero($data, ['water_quantity', 'cost']);

        return IrrigationSchedule::create($data);
    }

    public function createEquipment(array $data): Equipment
    {
        $data['equipment_code'] ??= $this->numbers->next('agriculture_equipment', 'equipment_code', 'EQP');
        $data = $this->zero($data, ['purchase_cost', 'current_value']);

        return Equipment::create($data);
    }

    public function createEquipmentMaintenance(array $data): EquipmentMaintenance
    {
        $equipment = Equipment::findOrFail($data['equipment_id']);
        $data['farm_id'] = $data['farm_id'] ?? $equipment->farm_id;
        $data['maintenance_number'] ??= $this->numbers->next('agriculture_equipment_maintenance', 'maintenance_number', 'EMT');
        $data['parts_used'] = $this->list($data['parts_used'] ?? null);
        $data = $this->zero($data, ['cost', 'meter_hours_reading']);
        $record = EquipmentMaintenance::create($data);
        $equipment->update(['status' => 'Available']);

        return $record;
    }

    public function createFarmer(array $data): Farmer
    {
        $data['farmer_number'] ??= $this->numbers->next('agriculture_farmers', 'farmer_number', 'FRM');
        $data['crops'] = $this->list($data['crops'] ?? null);
        $data = $this->zero($data, ['acreage', 'input_advances', 'deliveries_value', 'payments_value']);

        return Farmer::create($data);
    }

    public function createContract(array $data): FarmerContract
    {
        $data['contract_number'] ??= $this->numbers->next('agriculture_farmer_contracts', 'contract_number', 'CON');
        $data['inputs_provided'] = $this->list($data['inputs_provided'] ?? null);
        $data['delivery_dates'] = $this->list($data['delivery_dates'] ?? null);
        $data = $this->zero($data, ['acreage', 'expected_quantity', 'agreed_price']);

        return FarmerContract::create($data);
    }

    public function createWarehouse(array $data): ProduceWarehouse
    {
        $data['warehouse_code'] ??= $this->numbers->next('agriculture_produce_warehouses', 'warehouse_code', 'WH');
        $data = $this->zero($data, ['capacity']);

        return ProduceWarehouse::create($data);
    }

    public function createStorageBin(array $data): StorageBin
    {
        $warehouse = ProduceWarehouse::findOrFail($data['warehouse_id']);
        $data['farm_id'] = $data['farm_id'] ?? $warehouse->farm_id;
        $data['bin_code'] ??= $this->numbers->next('agriculture_storage_bins', 'bin_code', 'BIN');
        $data = $this->zero($data, ['capacity', 'temperature_c']);

        return StorageBin::create($data);
    }

    public function createWarehouseMovement(array $data): WarehouseMovement
    {
        $batch = ProduceBatch::findOrFail($data['produce_batch_id']);
        $data['farm_id'] = $data['farm_id'] ?? $batch->farm_id;
        $data['movement_number'] ??= $this->numbers->next('agriculture_warehouse_movements', 'movement_number', 'MOV');
        $data = $this->zero($data, ['loss_quantity']);

        return WarehouseMovement::create($data);
    }

    public function createProduceSale(array $data): ProduceSale
    {
        $data['sale_number'] ??= $this->numbers->next('agriculture_produce_sales', 'sale_number', 'SAL');
        $data['total'] = $data['total'] ?? ((float) $data['quantity'] * (float) $data['unit_price']);

        return DB::transaction(function () use ($data) {
            $batch = ! empty($data['produce_batch_id']) ? ProduceBatch::whereKey($data['produce_batch_id'])->lockForUpdate()->firstOrFail() : null;
            if ($batch && (float) $batch->quantity < (float) $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Produce batch quantity cannot go negative.']);
            }

            $sale = ProduceSale::create($data);
            if ($batch) {
                $batch->update(['quantity' => (float) $batch->quantity - (float) $data['quantity'], 'stage' => 'Sale']);
            }
            if (! empty($data['client_id'])) {
                $sale->update(['invoice_id' => $this->createSaleInvoice($sale)->id]);
            }

            return $sale->refresh();
        });
    }

    public function createCompliance(array $data, $file = null): ComplianceRecord
    {
        $data['compliance_number'] ??= $this->numbers->next('agriculture_compliance_records', 'compliance_number', 'CMP');
        if ($file) {
            $data['attachment_path'] = $file->store('agriculture/compliance', 'public');
        }

        return ComplianceRecord::create($data);
    }

    public function createBudget(array $data): BudgetLine
    {
        $data['budget_number'] ??= $this->numbers->next('agriculture_budget_lines', 'budget_number', 'BUD');
        $data = $this->zero($data, ['budget_amount', 'actual_amount', 'alert_threshold']);

        return BudgetLine::create($data);
    }

    public function createDocument(array $data, $file = null): AgricultureDocument
    {
        if ($file) {
            $data['file_path'] = $file->store('agriculture/documents', 'public');
        }

        return AgricultureDocument::create($data);
    }

    public function deleteDocument(AgricultureDocument $document): void
    {
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();
    }

    private function createSaleInvoice(ProduceSale $sale): Invoice
    {
        $client = Client::findOrFail($sale->client_id);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => $this->documents->number('invoice'),
            'invoice_date' => $sale->sale_date,
            'due_date' => $sale->sale_date,
            'payment_status' => 'unpaid',
            'subtotal' => $sale->total,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => $sale->total,
            'amount_paid' => 0,
            'balance' => $sale->total,
            'industry_module' => 'agriculture',
            'industry_reference' => $sale->sale_number,
            'recipient_profile' => ['name' => $client->name, 'buyer_type' => $sale->buyer_type],
            'industry_context' => ['produce_batch' => $sale->produceBatch?->batch_number, 'traceability_id' => $sale->produceBatch?->traceability_id],
        ]);

        $invoice->items()->create([
            'title' => 'Agricultural produce sale',
            'description' => 'Produce sale '.$sale->sale_number,
            'quantity' => $sale->quantity,
            'unit_price' => $sale->unit_price,
            'discount' => 0,
            'tax_rate' => 0,
            'line_total' => $sale->total,
        ]);

        return $invoice;
    }

    private function assertStock(Product $product, float $quantity): void
    {
        if ((float) $product->stock_quantity < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Product stock cannot go negative.']);
        }
    }

    private function zero(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $data[$field] = ($data[$field] ?? null) === '' || ($data[$field] ?? null) === null ? 0 : $data[$field];
        }

        return $data;
    }

    private function list($value): ?array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        if (! $value) {
            return null;
        }

        return collect(explode(',', $value))->map(fn ($item) => trim($item))->filter()->values()->all();
    }
}
