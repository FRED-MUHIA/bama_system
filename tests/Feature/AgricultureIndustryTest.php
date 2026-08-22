<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Client;
use App\Models\IamRole;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant as PlatformTenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Services\TenantProvisioningService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
use Modules\Agriculture\Models\FarmSeason;
use Modules\Agriculture\Models\FarmWorker;
use Modules\Agriculture\Models\Field;
use Modules\Agriculture\Models\Harvest;
use Modules\Agriculture\Models\Herd;
use Modules\Agriculture\Models\InputUsage;
use Modules\Agriculture\Models\ProduceBatch;
use Modules\Agriculture\Models\ProduceSale;
use Modules\Agriculture\Models\ProduceWarehouse;
use Modules\Agriculture\Models\VeterinaryRecord;
use Tests\TestCase;

class AgricultureIndustryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PlatformTenant $tenant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = PlatformTenant::create([
            'name' => 'Green Valley Farms',
            'slug' => 'green-valley-farms',
            'industry' => 'agriculture',
            'sub_industry' => 'enterprise',
            'status' => 'active',
        ]);
        ActiveTenant::switchTo($this->tenant);

        $this->business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Green Valley Farms',
            'slug' => 'green-valley-farms',
            'industry' => 'agriculture',
            'is_active' => true,
        ]);
        ActiveBusiness::switchTo($this->business);
        app(ModuleRegistry::class)->enableDefaultsFor($this->tenant);

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($this->admin)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);
        app(IamService::class)->bootstrap();
    }

    protected function tearDown(): void
    {
        foreach ([ActiveTenant::class => ['current', 'fallback', 'id', 'idResolved'], ActiveBusiness::class => ['current', 'default']] as $class => $properties) {
            $reflection = new \ReflectionClass($class);
            foreach ($properties as $property) {
                $ref = $reflection->getProperty($property);
                $ref->setAccessible(true);
                $ref->setValue(null, $property === 'idResolved' ? false : null);
            }
        }

        parent::tearDown();
    }

    public function test_agriculture_package_metadata_onboarding_and_navigation_are_complete(): void
    {
        $this->getJson('/api/v1/industry-packages/agriculture?sub_industry=enterprise')
            ->assertOk()
            ->assertJsonPath('industry', 'Agriculture')
            ->assertJsonPath('selected_sub_industry', 'enterprise')
            ->assertJsonFragment(['name' => 'Agricultural Produce Aggregator'])
            ->assertSee('Farm Analytics')
            ->assertSee('Agriculture Administrator');

        $owner = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $tenant = app(TenantProvisioningService::class)->provision([
            'tenant_name' => 'Dairy Prime',
            'business_name' => 'Dairy Prime',
            'industry' => 'agriculture',
            'sub_industry' => 'dairy-farming',
            'plan' => 'starter',
        ], $owner);

        $settings = $tenant->fresh()->settings;
        $this->assertSame('dairy-farming', $settings['agriculture_configuration']['sub_industry']);
        $this->assertContains('Livestock Management', $settings['agriculture_configuration']['enabled_modules']);

        ActiveTenant::switchTo($this->tenant);
        ActiveBusiness::switchTo($this->business);
        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();
        $this->assertContains('Farms', $labels);
        $this->assertContains('Crops', $labels);
        $this->assertContains('Livestock', $labels);
        $this->assertContains('Documents', $labels);
    }

    public function test_agriculture_lifecycle_inventory_sales_documents_reports_and_traceability_work(): void
    {
        Storage::fake('public');

        $this->post(route('agriculture.records.store', 'farms'), [
            'name' => 'North Farm',
            'location' => 'Nakuru',
            'county_region' => 'Rift Valley',
            'total_area' => 100,
            'measurement_unit' => 'Acres',
            'ownership_type' => 'Owned',
            'manager_id' => $this->admin->id,
            'status' => 'Active',
        ])->assertRedirect(route('agriculture.dashboard', ['section' => 'farms']));
        $farm = Farm::firstOrFail();
        $this->assertMatchesRegularExpression('/^FARM-2026-\d{5}$/', $farm->farm_code);

        $this->post(route('agriculture.records.store', 'fields'), [
            'farm_id' => $farm->id,
            'name' => 'Block A',
            'size' => 40,
            'measurement_unit' => 'Acres',
            'soil_type' => 'Loam',
            'irrigation_type' => 'Drip',
            'latitude' => -0.303,
            'longitude' => 36.08,
            'status' => 'Available',
        ])->assertRedirect();
        $field = Field::firstOrFail();

        $this->post(route('agriculture.records.store', 'fields'), [
            'farm_id' => $farm->id,
            'name' => 'Oversized Block',
            'size' => 80,
            'measurement_unit' => 'Acres',
            'irrigation_type' => 'Rain-fed',
            'status' => 'Available',
        ])->assertSessionHasErrors('size');

        $this->post(route('agriculture.records.store', 'plots'), [
            'field_id' => $field->id,
            'name' => 'A1',
            'size' => 10,
            'status' => 'Available',
        ])->assertRedirect();

        $this->post(route('agriculture.records.store', 'seasons'), [
            'farm_id' => $farm->id,
            'name' => 'Long Rains 2026',
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-08-31',
            'status' => 'Open',
        ])->assertRedirect();
        $season = FarmSeason::firstOrFail();

        $this->post(route('agriculture.records.store', 'workers'), [
            'farm_id' => $farm->id,
            'field_id' => $field->id,
            'name' => 'Field Supervisor',
            'role_title' => 'Farm Supervisor',
            'duties' => 'Irrigation, scouting',
            'status' => 'Active',
        ])->assertRedirect();
        $worker = FarmWorker::firstOrFail();

        $this->post(route('agriculture.records.store', 'crops'), [
            'name' => 'Maize',
            'category' => 'Cereals',
            'variety' => 'H614',
            'expected_growing_period_days' => 120,
            'recommended_planting_season' => 'Long Rains',
            'expected_yield' => 8000,
            'yield_unit' => 'kg',
        ])->assertRedirect();
        $crop = Crop::firstOrFail();

        $this->post(route('agriculture.records.store', 'crop-plans'), [
            'field_id' => $field->id,
            'season_id' => $season->id,
            'crop_id' => $crop->id,
            'manager_id' => $this->admin->id,
            'planting_date' => '2026-03-10',
            'expected_harvest_date' => '2026-08-20',
            'planned_acreage' => 40,
            'seed_quantity' => 100,
            'expected_yield' => 8000,
            'budget' => 250000,
            'status' => 'Planted',
        ])->assertRedirect();
        $plan = CropPlan::firstOrFail();
        $this->assertSame('Planted', $field->fresh()->status);

        $this->post(route('agriculture.records.store', 'crop-plans'), [
            'field_id' => $field->id,
            'crop_id' => $crop->id,
            'status' => 'Draft',
        ])->assertSessionHasErrors('crop_id');

        $this->post(route('agriculture.records.store', 'activities'), [
            'crop_plan_id' => $plan->id,
            'assigned_worker_id' => $worker->id,
            'activity_type' => 'Fertilizer Application',
            'scheduled_date' => '2026-04-01',
            'inputs_used' => 'DAP, CAN',
            'cost' => 15000,
            'status' => 'Completed',
        ])->assertRedirect();
        $activity = FarmActivity::firstOrFail();

        $product = Product::create(['name' => 'DAP Fertilizer', 'sku' => 'DAP-001', 'price' => 0, 'cost_price' => 2500, 'stock_quantity' => 50, 'reorder_level' => 5, 'stock_unit' => 'bag', 'is_active' => true]);
        $this->post(route('agriculture.records.store', 'inputs'), [
            'product_id' => $product->id,
            'name' => 'DAP Fertilizer',
            'category' => 'Fertilizers',
            'batch_number' => 'DAP-B1',
            'expiry_date' => '2027-01-01',
            'quantity_on_hand' => 30,
            'unit_cost' => 2500,
            'reorder_level' => 5,
            'unit' => 'bag',
            'status' => 'Active',
        ])->assertRedirect();
        $input = AgricultureInput::firstOrFail();

        $this->post(route('agriculture.records.store', 'input-usage'), [
            'input_id' => $input->id,
            'crop_plan_id' => $plan->id,
            'activity_id' => $activity->id,
            'worker_id' => $worker->id,
            'usage_date' => '2026-04-01',
            'quantity_used' => 5,
        ])->assertRedirect();
        $this->assertSame(25.0, (float) $input->fresh()->quantity_on_hand);
        $this->assertSame(45.0, (float) $product->fresh()->stock_quantity);
        $this->assertInstanceOf(StockMovement::class, StockMovement::first());

        $this->post(route('agriculture.records.store', 'input-usage'), [
            'input_id' => $input->id,
            'crop_plan_id' => $plan->id,
            'usage_date' => '2026-04-02',
            'quantity_used' => 100,
        ])->assertSessionHasErrors('quantity_used');

        $this->post(route('agriculture.records.store', 'harvests'), [
            'field_id' => $field->id,
            'crop_plan_id' => $plan->id,
            'harvest_date' => '2026-08-20',
            'quantity' => 7000,
            'measurement_unit' => 'kg',
            'grade' => 'Grade A',
            'quality' => 'Dry',
            'waste_quantity' => 100,
            'destination' => 'Storage',
            'storage_location' => 'Warehouse 1',
            'value' => 420000,
        ])->assertRedirect();
        $harvest = Harvest::firstOrFail();
        $batch = ProduceBatch::firstOrFail();
        $this->assertSame($harvest->id, $batch->harvest_id);
        $this->assertStringStartsWith('AGR-'.$this->tenant->id.'-PRD-', $batch->traceability_id);

        $this->post(route('agriculture.records.store', 'warehouses'), [
            'farm_id' => $farm->id,
            'name' => 'Main Produce Store',
            'warehouse_type' => 'Produce Warehouse',
            'capacity' => 10000,
            'status' => 'Active',
        ])->assertRedirect();
        $warehouse = ProduceWarehouse::firstOrFail();

        $this->post(route('agriculture.records.store', 'warehouse-movements'), [
            'produce_batch_id' => $batch->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'Stored',
            'movement_date' => '2026-08-21',
            'quantity' => 7000,
        ])->assertRedirect();

        $buyer = Client::create(['name' => 'Fresh Foods Ltd', 'type' => 'company', 'email' => 'fresh@example.test']);
        $this->post(route('agriculture.records.store', 'sales'), [
            'farm_id' => $farm->id,
            'client_id' => $buyer->id,
            'produce_batch_id' => $batch->id,
            'buyer_type' => 'Wholesaler',
            'sale_date' => '2026-08-22',
            'quantity' => 1000,
            'unit_price' => 60,
            'delivery_status' => 'Pending',
            'payment_status' => 'Unpaid',
        ])->assertRedirect();
        $sale = ProduceSale::firstOrFail();
        $this->assertSame(60000.0, (float) $sale->total);
        $this->assertSame(6000.0, (float) $batch->fresh()->quantity);
        $this->assertSame('agriculture', Invoice::firstOrFail()->industry_module);

        $this->post(route('agriculture.records.store', 'herds'), [
            'farm_id' => $farm->id,
            'name' => 'Dairy Herd',
            'category' => 'Dairy Cattle',
            'breed' => 'Friesian',
            'status' => 'Active',
        ])->assertRedirect();
        $herd = Herd::firstOrFail();

        $this->post(route('agriculture.records.store', 'animals'), [
            'farm_id' => $farm->id,
            'herd_id' => $herd->id,
            'tag_number' => 'COW-001',
            'name' => 'Bella',
            'species' => 'Dairy Cattle',
            'breed' => 'Friesian',
            'gender' => 'Female',
            'date_of_birth' => '2024-01-01',
            'weight' => 420,
            'status' => 'Active',
        ])->assertRedirect();
        $animal = Animal::firstOrFail();

        $this->post(route('agriculture.records.store', 'veterinary'), [
            'animal_id' => $animal->id,
            'record_type' => 'Vaccination',
            'record_date' => '2026-08-01',
            'medication' => 'FMD',
            'treatment_cost' => 2500,
            'next_due_date' => '2026-09-01',
            'recovery_status' => 'Monitoring',
        ])->assertRedirect();
        $this->assertInstanceOf(VeterinaryRecord::class, VeterinaryRecord::first());

        $this->post(route('agriculture.records.store', 'breeding'), [
            'animal_id' => $animal->id,
            'method' => 'Artificial Insemination',
            'event_date' => '2026-08-02',
            'expected_birth_date' => '2027-05-10',
            'status' => 'Pending',
        ])->assertRedirect();
        $this->assertInstanceOf(BreedingEvent::class, BreedingEvent::first());

        $this->post(route('agriculture.records.store', 'equipment'), [
            'farm_id' => $farm->id,
            'name' => 'Tractor 1',
            'equipment_type' => 'Tractor',
            'serial_number' => 'TR-1',
            'purchase_cost' => 2500000,
            'current_value' => 2000000,
            'fuel_type' => 'Diesel',
            'status' => 'Available',
        ])->assertRedirect();
        $equipment = Equipment::firstOrFail();

        $this->post(route('agriculture.records.store', 'equipment-maintenance'), [
            'equipment_id' => $equipment->id,
            'service_date' => '2026-08-05',
            'service_type' => 'Oil Change',
            'parts_used' => 'Oil filter',
            'cost' => 12000,
            'next_service_date' => '2026-09-05',
            'meter_hours_reading' => 900,
            'status' => 'Completed',
        ])->assertRedirect();
        $this->assertInstanceOf(EquipmentMaintenance::class, EquipmentMaintenance::first());

        $this->post(route('agriculture.records.store', 'compliance'), [
            'farm_id' => $farm->id,
            'compliance_type' => 'Organic Certification',
            'certificate_number' => 'ORG-2026',
            'issue_date' => '2026-01-01',
            'expiry_date' => '2026-09-01',
            'status' => 'Active',
            'attachment' => UploadedFile::fake()->create('organic.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->assertInstanceOf(ComplianceRecord::class, ComplianceRecord::first());

        $this->post(route('agriculture.records.store', 'budgets'), [
            'farm_id' => $farm->id,
            'field_id' => $field->id,
            'crop_plan_id' => $plan->id,
            'budget_type' => 'Crop',
            'category' => 'Fertilizer',
            'fiscal_year' => 2026,
            'budget_amount' => 20000,
            'actual_amount' => 22000,
            'alert_threshold' => 90,
            'status' => 'Open',
        ])->assertRedirect();
        $this->assertInstanceOf(BudgetLine::class, BudgetLine::first());

        $this->post(route('agriculture.records.store', 'documents'), [
            'farm_id' => $farm->id,
            'documentable_type' => 'farm',
            'documentable_id' => $farm->id,
            'document_type' => 'Farm License',
            'title' => 'North Farm License',
            'status' => 'Active',
            'file' => UploadedFile::fake()->create('farm-license.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('agriculture.dashboard', ['section' => 'documents']));
        $document = AgricultureDocument::firstOrFail();
        Storage::disk('public')->assertExists($document->file_path);
        $this->get(route('agriculture.documents.download', $document))->assertOk();

        $this->get(route('agriculture.dashboard', ['section' => 'farms']))
            ->assertOk()
            ->assertSee('Agriculture - Enterprise Agriculture')
            ->assertSee('North Farm');
        $this->getJson(route('api.v1.agriculture.dashboard'))->assertOk()->assertJsonPath('data.metrics.total_farms', 1);
        $this->getJson(route('api.v1.agriculture.traceability.show', $batch))->assertOk()->assertJsonPath('data.produce_batch', $batch->batch_number);

        foreach (['farms', 'fields', 'crop-plans', 'activities', 'harvests', 'livestock', 'veterinary', 'inputs', 'equipment', 'equipment-maintenance', 'sales', 'finance', 'compliance'] as $type) {
            $this->get(route('agriculture.reports.csv', $type))->assertOk()->assertDownload("agriculture-{$type}.csv");
            $this->getJson(route('api.v1.agriculture.reports.export', $type))->assertOk()->assertDownload("agriculture-{$type}.csv");
        }

        $this->delete(route('agriculture.documents.destroy', $document))->assertRedirect();
        Storage::disk('public')->assertMissing($document->file_path);
        $this->assertModelMissing($document);
    }

    public function test_agriculture_records_are_tenant_isolated_and_permissions_are_enforced(): void
    {
        Farm::create(['farm_code' => 'FARM-A', 'name' => 'Tenant Farm', 'total_area' => 10, 'measurement_unit' => 'Acres', 'ownership_type' => 'Owned', 'status' => 'Active']);
        $this->assertSame(1, Farm::count());

        $otherTenant = PlatformTenant::create(['name' => 'Other Farm Co', 'slug' => 'other-farm-co', 'industry' => 'agriculture', 'sub_industry' => 'crop-farming', 'status' => 'active']);
        $otherBusiness = Business::withoutGlobalScopes()->create(['tenant_id' => $otherTenant->id, 'name' => 'Other Farm Co', 'slug' => 'other-farm-co', 'industry' => 'agriculture', 'is_active' => true]);
        ActiveTenant::switchTo($otherTenant);
        ActiveBusiness::switchTo($otherBusiness);

        $this->assertSame(0, Farm::count());

        ActiveTenant::switchTo($this->tenant);
        ActiveBusiness::switchTo($this->business);

        $viewer = User::factory()->create(['role' => 'staff', 'is_active' => true, 'status' => 'Active']);
        $viewerRole = IamRole::where('business_id', $this->business->id)->where('slug', 'agriculture-viewer')->firstOrFail();
        DB::table('business_user')->insert([
            'business_id' => $this->business->id,
            'user_id' => $viewer->id,
            'iam_role_id' => $viewerRole->id,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($viewer)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);

        $this->post(route('agriculture.records.store', 'farms'), [
            'name' => 'Blocked Farm',
            'total_area' => 10,
            'measurement_unit' => 'Acres',
            'ownership_type' => 'Owned',
            'status' => 'Active',
        ])->assertForbidden();
    }
}
